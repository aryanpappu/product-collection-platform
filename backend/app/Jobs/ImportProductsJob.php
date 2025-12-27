<?php

namespace App\Jobs;

use App\Events\ImportCompleted;
use App\Models\ImportJob;
use App\Models\ImportJobLog;
use App\Services\CsvFileProcessor;
use App\Services\ProductBulkInsertService;
use App\Validators\ProductImportValidator;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ImportProductsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 3600;
    public array $backoff = [60, 300, 900];

    private const int CHUNK_SIZE = 500;
    private const int REDIS_PROGRESS_TTL = 3600;

    public int $importJobId;
    private CsvFileProcessor $csvProcessor;
    private ProductImportValidator $validator;
    private ProductBulkInsertService $bulkInsertService;


    public function __construct(int $importJobId)
    {
        $this->importJobId = $importJobId;
    }


    public function handle(): void
    {
        $this->initializeServices();

        $importJob = ImportJob::with('merchant')->findOrFail($this->importJobId);

        try {
            $this->startImport($importJob);
            $this->processImport($importJob);
            $this->completeImport($importJob);
        } catch (Exception $e) {
            $this->failImport($importJob, $e);
            throw $e;
        }
    }


    private function initializeServices(): void
    {
        $this->csvProcessor = new CsvFileProcessor();
        $this->validator = new ProductImportValidator();
        $this->bulkInsertService = new ProductBulkInsertService();
    }


    private function startImport(ImportJob $importJob): void
    {
        $importJob->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $this->logInfo('Import job started', [
            'import_job_id' => $this->importJobId,
        ]);
    }


    private function processImport(ImportJob $importJob): void
    {
        $filePath = $this->getFilePath($importJob);

        $this->csvProcessor->open($filePath);
        $this->validateCsvHeader();
        $this->updateTotalRows($importJob);
        $this->processAllChunks($importJob);
        $this->csvProcessor->close();
    }


    private function completeImport(ImportJob $importJob): void
    {
        $importJob->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->logImportCompletion($importJob);
        $this->sendCompletionNotification($importJob);
        $this->cleanupProgress($importJob);
    }


    private function failImport(ImportJob $importJob, Exception $e): void
    {
        $importJob->update([
            'status' => 'failed',
            'error_message' => $e->getMessage(),
            'completed_at' => now(),
        ]);

        $this->logError('Import job failed', [
            'import_job_id' => $importJob->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }


    private function getFilePath(ImportJob $importJob): string
    {
        return storage_path('app/private/' . $importJob->file_path);
    }


    private function validateCsvHeader(): void
    {
        $header = $this->csvProcessor->getHeader();
        $this->validator->validateHeader($header);
    }


    private function updateTotalRows(ImportJob $importJob): void
    {
        $totalRows = $this->csvProcessor->countRows();
        $importJob->update(['total_rows' => $totalRows]);
    }


    private function processAllChunks(ImportJob $importJob): void
    {
        $stats = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
        ];

        while ($chunk = $this->csvProcessor->readChunk(self::CHUNK_SIZE)) {
            if (empty($chunk)) {
                break;
            }

            $result = $this->processChunk($importJob, $chunk);
            $stats = $this->updateStats($stats, $result, count($chunk));
            $this->updateProgress($importJob, $stats);
        }
    }


    private function updateStats(array $stats, array $result, int $chunkSize): array
    {
        return [
            'processed' => $stats['processed'] + $chunkSize,
            'successful' => $stats['successful'] + $result['successful'],
            'failed' => $stats['failed'] + $result['failed'],
        ];
    }


    private function processChunk(ImportJob $importJob, array $chunk): array
    {
        $productsToInsert = [];
        $successful = 0;
        $failed = 0;

        foreach ($chunk as $item) {
            $result = $this->processRow($importJob, $item);

            if ($result['success']) {
                $productsToInsert[] = $result['product'];
                $successful++;
            } else {
                $failed++;
            }
        }

        if (!empty($productsToInsert)) {
            $insertResult = $this->insertProducts($productsToInsert);

            if (!$insertResult['success']) {
                $successful = $insertResult['successful'];
                $failed += $insertResult['failed'];
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
        ];
    }


    private function processRow(ImportJob $importJob, array $item): array
    {
        try {
            $validated = $this->validator->validateRow($item['data']);
            $product = $this->prepareProductData($importJob, $validated);

            return [
                'success' => true,
                'product' => $product,
            ];
        } catch (Exception $e) {
            $this->logRowError($importJob, $item['number'], $item['data'], $e->getMessage());

            return [
                'success' => false,
            ];
        }
    }


    private function prepareProductData(ImportJob $importJob, array $validated): array
    {
        return [
            'merchant_id' => $importJob->merchant_id,
            'sku' => $validated['sku'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }


    private function insertProducts(array $products): array
    {
        $success = $this->bulkInsertService->bulkInsert($products);

        if ($success) {
            return ['success' => true];
        }

        $result = $this->bulkInsertService->insertOneByOne($products);

        return [
            'success' => false,
            'successful' => $result['successful'],
            'failed' => $result['failed'],
        ];
    }


    private function logRowError(ImportJob $importJob, int $rowNumber, array $row, string $errorMessage): void
    {
        ImportJobLog::create([
            'import_job_id' => $importJob->id,
            'row_number' => $rowNumber,
            'row_data' => json_encode($row),
            'error_type' => 'validation',
            'error_message' => $errorMessage,
            'created_at' => now(),
        ]);
    }


    private function updateProgress(ImportJob $importJob, array $stats): void
    {
        $this->updateDatabaseProgress($importJob, $stats);
        $this->updateRedisProgress($importJob, $stats);
    }


    private function updateDatabaseProgress(ImportJob $importJob, array $stats): void
    {
        $importJob->update([
            'processed_rows' => $stats['processed'],
            'successful_rows' => $stats['successful'],
            'failed_rows' => $stats['failed'],
        ]);
    }


    private function updateRedisProgress(ImportJob $importJob, array $stats): void
    {
        $progressKey = $this->getProgressKey($importJob);
        $progressData = $this->buildProgressData($importJob, $stats);

        Redis::setex($progressKey, self::REDIS_PROGRESS_TTL, json_encode($progressData));
    }


    private function buildProgressData(ImportJob $importJob, array $stats): array
    {
        return [
            'import_job_id' => $importJob->id,
            'total_rows' => $importJob->total_rows,
            'processed_rows' => $stats['processed'],
            'successful_rows' => $stats['successful'],
            'failed_rows' => $stats['failed'],
            'percentage' => $this->calculatePercentage($importJob, $stats['processed']),
            'status' => 'processing',
            'updated_at' => now()->toIso8601String(),
        ];
    }

    private function calculatePercentage(ImportJob $importJob, int $processed): float
    {
        if ($importJob->total_rows <= 0) {
            return 0;
        }

        return round(($processed / $importJob->total_rows) * 100, 2);
    }

    private function getProgressKey(ImportJob $importJob): string
    {
        return "import_progress:{$importJob->id}";
    }


    private function logImportCompletion(ImportJob $importJob): void
    {
        $this->logInfo('Import job completed', [
            'import_job_id' => $this->importJobId,
            'total_rows' => $importJob->total_rows,
            'successful_rows' => $importJob->successful_rows,
            'failed_rows' => $importJob->failed_rows,
        ]);
    }


    private function sendCompletionNotification(ImportJob $importJob): void
    {
        ImportCompleted::dispatch($importJob);

        $this->logInfo('Import completion event dispatched', [
            'merchant_email' => $importJob->merchant->email,
            'import_job_id' => $importJob->id,
            'total_rows' => $importJob->total_rows,
            'successful_rows' => $importJob->successful_rows,
            'failed_rows' => $importJob->failed_rows,
        ]);
    }


    private function cleanupProgress(ImportJob $importJob): void
    {
        Redis::del($this->getProgressKey($importJob));
    }


    private function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $this->buildLogContext($context));
    }


    private function logError(string $message, array $context = []): void
    {
        Log::error($message, $this->buildLogContext($context));
    }


    private function buildLogContext(array $context): array
    {
        return array_merge($context, [
            'job' => 'ImportProductsJob',
        ]);
    }
}
