<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductImportRequest;
use App\Jobs\ImportProductsJob;
use App\Models\ImportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImportController extends Controller
{

    public function import(ProductImportRequest $request): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);
        $file = $request->file('file');
        $originalFilename = $file->getClientOriginalName();

        $storedPath = $this->storeImportFile($file, $merchantId, $originalFilename);

        if (!$storedPath) {
            return $this->errorResponse('File upload failed', 'Failed to store uploaded file', 500);
        }

        if (!Storage::disk('local')->exists($storedPath)) {
            return $this->errorResponse(
                'File upload failed',
                'File upload verification failed. Path: ' . $storedPath,
                500
            );
        }

        $importJob = $this->createImportJob($merchantId, $originalFilename, $storedPath);

        ImportProductsJob::dispatch($importJob->id);

        return $this->successResponse(
            [
                'import_job_id' => $importJob->id,
                'filename' => $originalFilename,
                'status' => $importJob->status,
            ],
            'Import job queued successfully',
            202
        );
    }


    public function status(Request $request, int $importJobId): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);
        $importJob = $this->findImportJob($importJobId, $merchantId);

        if (!$importJob) {
            return $this->notFoundResponse('Import job not found');
        }

        return $this->successResponse($this->formatImportJobDetails($importJob));
    }


    public function list(Request $request): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);

        $importJobs = ImportJob::where('merchant_id', $merchantId)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->paginatedResponse($importJobs, fn($importJob) => $this->formatImportJobSummary($importJob));
    }


    public function errors(Request $request, int $importJobId): JsonResponse
    {
        $merchantId = $this->getMerchantId($request);
        $importJob = $this->findImportJob($importJobId, $merchantId);

        if (!$importJob) {
            return $this->notFoundResponse('Import job not found');
        }

        $errors = $importJob->logs()
            ->orderBy('row_number')
            ->paginate(50);

        return $this->paginatedResponse($errors, fn($log) => $this->formatErrorLog($log));
    }


    private function getMerchantId(Request $request): int
    {
        return $request->attributes->get('merchant_id');
    }


    private function findImportJob(int $importJobId, int $merchantId): ?ImportJob
    {
        return ImportJob::where('id', $importJobId)
            ->where('merchant_id', $merchantId)
            ->first();
    }


    private function storeImportFile($file, int $merchantId, string $originalFilename): ?string
    {
        $filename = time() . '_' . Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) . '.csv';
        $directory = "imports/{$merchantId}";

        Storage::disk('local')->makeDirectory($directory);

        return $file->storeAs($directory, $filename, 'local');
    }


    private function createImportJob(int $merchantId, string $originalFilename, string $storedPath): ImportJob
    {
        return ImportJob::create([
            'merchant_id' => $merchantId,
            'filename' => $originalFilename,
            'file_path' => $storedPath,
            'status' => 'queued',
        ]);
    }


    private function formatImportJobDetails(ImportJob $importJob): array
    {
        return [
            'import_job_id' => $importJob->id,
            'filename' => $importJob->filename,
            'status' => $importJob->status,
            'total_rows' => $importJob->total_rows,
            'processed_rows' => $importJob->processed_rows,
            'successful_rows' => $importJob->successful_rows,
            'failed_rows' => $importJob->failed_rows,
            'progress_percentage' => $importJob->getProgressPercentage(),
            'error_message' => $importJob->error_message,
            'started_at' => $importJob->started_at?->toIso8601String(),
            'completed_at' => $importJob->completed_at?->toIso8601String(),
            'created_at' => $importJob->created_at->toIso8601String(),
        ];
    }


    private function formatImportJobSummary(ImportJob $importJob): array
    {
        return [
            'import_job_id' => $importJob->id,
            'filename' => $importJob->filename,
            'status' => $importJob->status,
            'total_rows' => $importJob->total_rows,
            'processed_rows' => $importJob->processed_rows,
            'successful_rows' => $importJob->successful_rows,
            'failed_rows' => $importJob->failed_rows,
            'progress_percentage' => $importJob->getProgressPercentage(),
            'started_at' => $importJob->started_at?->toIso8601String(),
            'completed_at' => $importJob->completed_at?->toIso8601String(),
            'created_at' => $importJob->created_at->toIso8601String(),
        ];
    }


    private function formatErrorLog($log): array
    {
        return [
            'row_number' => $log->row_number,
            'error_type' => $log->error_type,
            'error_message' => $log->error_message,
            'row_data' => json_decode($log->row_data, true),
            'created_at' => $log->created_at->toIso8601String(),
        ];
    }
}
