<?php

namespace App\Jobs;

use App\Models\Collection;
use App\Models\Product;
use Exception;
use Illuminate\Bus\Queueable as BusQueueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkAddProductsToCollectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, BusQueueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;
    public array $backoff = [30, 60, 120];

    private const int CHUNK_SIZE = 1000;

    public int $collectionId;
    public array $productIds;
    public int $merchantId;


    public function __construct(int $collectionId, array $productIds, int $merchantId)
    {
        $this->collectionId = $collectionId;
        $this->productIds = $productIds;
        $this->merchantId = $merchantId;
    }


    /**
     * Get the middleware the job should pass through.
     * Prevents multiple jobs from processing the same collection simultaneously.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("collection:{$this->collectionId}"))
                ->releaseAfter(60)
                ->expireAfter(600)
        ];
    }


    public function handle(): void
    {
        try {
            $this->logInfo('Starting bulk add products to collection', [
                'collection_id' => $this->collectionId,
                'product_count' => count($this->productIds),
            ]);

            $validProductIds = $this->getValidProductIds();

            if (empty($validProductIds)) {
                $this->logWarning('No valid products found to add', [
                    'collection_id' => $this->collectionId,
                ]);
                return;
            }

            $this->addProductsInChunks($validProductIds);

            $this->logInfo('Bulk add products completed', [
                'collection_id' => $this->collectionId,
                'products_added' => count($validProductIds),
            ]);
        } catch (Exception $e) {
            $this->logError('Bulk add products failed', [
                'collection_id' => $this->collectionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }


    private function getValidProductIds(): array
    {
        return Product::where('merchant_id', $this->merchantId)
            ->whereIn('id', $this->productIds)
            ->pluck('id')
            ->toArray();
    }


    private function addProductsInChunks(array $productIds): void
    {
        $chunks = array_chunk($productIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $this->addProductChunk($chunk);
        }
    }


    /**
     * @throws Exception
     */
    private function addProductChunk(array $productIds): void
    {
        try {
            DB::transaction(function () use ($productIds) {
                $collection = Collection::where('id', $this->collectionId)
                    ->where('merchant_id', $this->merchantId)
                    ->lockForUpdate()
                    ->first();

                if (!$collection) {
                    throw new Exception('Collection not found or deleted');
                }

                $existingProductIds = DB::table('collection_product')
                    ->where('collection_id', $collection->id)
                    ->whereIn('product_id', $productIds)
                    ->pluck('product_id')
                    ->toArray();

                $newProductIds = array_diff($productIds, $existingProductIds);

                if (!empty($newProductIds)) {

                    $maxPosition = DB::table('collection_product')
                        ->where('collection_id', $collection->id)
                        ->max('position') ?? 0;

                    $insertData = [];
                    $timestamp = now();

                    foreach ($newProductIds as $productId) {
                        $maxPosition++;
                        $insertData[] = [
                            'collection_id' => $collection->id,
                            'product_id' => $productId,
                            'position' => $maxPosition,
                            'added_at' => $timestamp,
                        ];
                    }

                    DB::table('collection_product')->insert($insertData);

                    $collection->products_count += count($newProductIds);
                    $collection->save();

                    $this->logInfo('Added products chunk', [
                        'collection_id' => $collection->id,
                        'new_products' => count($newProductIds),
                        'skipped_existing' => count($existingProductIds),
                    ]);
                }
            });
        } catch (Exception $e) {
            $this->logError('Failed to add product chunk', [
                'collection_id' => $this->collectionId,
                'chunk_size' => count($productIds),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }


    private function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $this->buildLogContext($context));
    }


    private function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, $this->buildLogContext($context));
    }


    private function logError(string $message, array $context = []): void
    {
        Log::error($message, $this->buildLogContext($context));
    }


    private function buildLogContext(array $context): array
    {
        return array_merge($context, [
            'job' => 'BulkAddProductsToCollectionJob',
            'merchant_id' => $this->merchantId,
        ]);
    }
}
