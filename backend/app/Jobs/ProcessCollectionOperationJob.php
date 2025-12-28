<?php

namespace App\Jobs;

use App\Models\CollectionOperationJob;
use App\Models\CollectionOperationLog;
use App\Models\Product;
use App\Services\CollectionCsvProcessor;
use App\Validators\CollectionOperationValidator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessCollectionOperationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600;
    public $tries = 3;
    public $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public CollectionOperationJob $operationJob
    )
    {
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->operationJob->id))
                ->releaseAfter(60)
                ->expireAfter(3600),
        ];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->operationJob->markAsProcessing();

            Log::info("Processing collection operation job #{$this->operationJob->id}");

            $filePath = Storage::path($this->operationJob->file_path);
            $validator = new CollectionOperationValidator();
            $processor = new CollectionCsvProcessor($filePath, $validator);

            $processor->open();

            $chunkNumber = 0;

            foreach ($processor->readChunk(500) as $chunk) {
                $chunkNumber++;
                Log::info("Processing chunk #{$chunkNumber} with " . count($chunk) . " rows");

                $this->processChunk($chunk, $validator);
            }

            $processor->close();

            $this->operationJob->markAsCompleted();

            Log::info("Collection operation job #{$this->operationJob->id} completed successfully");

        } catch (\Exception $e) {
            Log::error("Collection operation job #{$this->operationJob->id} failed: " . $e->getMessage());
            $this->operationJob->markAsFailed();
            throw $e;
        }
    }

    /**
     * Process a chunk of CSV rows
     */
    private function processChunk(array $chunk, CollectionOperationValidator $validator): void
    {
        foreach ($chunk as $item) {
            $rowNumber = $item['row_number'];
            $rowData = $item['data'];

            try {
                // Validate row
                if (!$validator->validate($rowData, $rowNumber)) {
                    $this->logError($rowNumber, $rowData, 'validation_error', implode(', ', $validator->getErrors()));
                    $this->operationJob->incrementProcessed();
                    $this->operationJob->incrementFailed();
                    continue;
                }

                // Sanitize data
                $sanitized = $validator->sanitize($rowData);

                // Find product by ID or SKU
                $product = $this->findProduct($sanitized['product_id'], $sanitized['sku']);

                if (!$product) {
                    $this->logError($rowNumber, $rowData, 'product_not_found', 'Product not found with given product_id or sku');
                    $this->operationJob->incrementProcessed();
                    $this->operationJob->incrementFailed();
                    continue;
                }

                // Verify product belongs to merchant
                if ($product->merchant_id !== $this->operationJob->merchant_id) {
                    $this->logError($rowNumber, $rowData, 'unauthorized', 'Product does not belong to this merchant');
                    $this->operationJob->incrementProcessed();
                    $this->operationJob->incrementFailed();
                    continue;
                }

                // Process operation with database lock to handle concurrency
                $this->processProductOperation($product, $sanitized['stock'], $rowNumber, $rowData);

                $this->operationJob->incrementProcessed();
                $this->operationJob->incrementSuccessful();

            } catch (\Exception $e) {
                Log::error("Error processing row {$rowNumber}: " . $e->getMessage());
                $this->logError($rowNumber, $rowData, 'processing_error', $e->getMessage());
                $this->operationJob->incrementProcessed();
                $this->operationJob->incrementFailed();
            }
        }
    }

    /**
     * Find product by ID or SKU
     */
    private function findProduct(?int $productId, ?string $sku): ?Product
    {
        if ($productId) {
            return Product::find($productId);
        }

        if ($sku) {
            return Product::where('sku', $sku)
                ->where('merchant_id', $this->operationJob->merchant_id)
                ->first();
        }

        return null;
    }

    /**
     * Process product operation (add/remove) with stock management
     * Uses database locking to prevent race conditions when multiple operations affect same product
     */
    private function processProductOperation(Product $product, int $stockAmount, int $rowNumber, array $rowData): void
    {
        DB::transaction(function () use ($product, $stockAmount, $rowNumber, $rowData) {
            // Lock the product row for update to prevent race conditions
            $product = Product::where('id', $product->id)->lockForUpdate()->first();

            if ($this->operationJob->operation_type === 'add') {
                // Add product to collection
                $this->operationJob->collection->products()->syncWithoutDetaching([
                    $product->id => [
                        'position' => $this->operationJob->collection->products()->count() + 1,
                        'added_at' => now(),
                    ],
                ]);

                // Increase stock
                $product->increment('stock', $stockAmount);
                $this->operationJob->addStockAmount($stockAmount);

                Log::info("Added product {$product->id} to collection {$this->operationJob->collection_id}, increased stock by {$stockAmount}");

            } elseif ($this->operationJob->operation_type === 'remove') {
                // Check if product is in collection
                if (!$this->operationJob->collection->products()->where('product_id', $product->id)->exists()) {
                    throw new \Exception("Product {$product->id} is not in collection {$this->operationJob->collection_id}");
                }

                // Check if stock is sufficient
                if ($product->stock < $stockAmount) {
                    throw new \Exception("Insufficient stock. Current: {$product->stock}, Requested: {$stockAmount}");
                }

                // Remove product from collection
                $this->operationJob->collection->products()->detach($product->id);

                // Decrease stock
                $product->decrement('stock', $stockAmount);
                $this->operationJob->removeStockAmount($stockAmount);

                Log::info("Removed product {$product->id} from collection {$this->operationJob->collection_id}, decreased stock by {$stockAmount}");
            }
        });
    }

    /**
     * Log error for a specific row
     */
    private function logError(int $rowNumber, array $rowData, string $errorType, string $errorMessage): void
    {
        CollectionOperationLog::create([
            'collection_operation_job_id' => $this->operationJob->id,
            'row_number' => $rowNumber,
            'row_data' => $rowData,
            'error_type' => $errorType,
            'error_message' => $errorMessage,
        ]);
    }
}
