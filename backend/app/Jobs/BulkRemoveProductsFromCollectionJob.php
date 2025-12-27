<?php

namespace App\Jobs;

use App\Models\Collection;
use Exception;
use Illuminate\Bus\Queueable as BusQueueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkRemoveProductsFromCollectionJob implements ShouldQueue
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
            $this->logInfo('Starting bulk remove products from collection', [
                'collection_id' => $this->collectionId,
                'product_count' => count($this->productIds),
            ]);

            $this->removeProductsInChunks($this->productIds);

            $this->logInfo('Bulk remove products completed', [
                'collection_id' => $this->collectionId,
                'products_to_remove' => count($this->productIds),
            ]);
        } catch (Exception $e) {
            $this->logError('Bulk remove products failed', [
                'collection_id' => $this->collectionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }


    private function removeProductsInChunks(array $productIds): void
    {
        $chunks = array_chunk($productIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $this->removeProductChunk($chunk);
        }
    }


    /**
     * @throws Exception
     */
    private function removeProductChunk(array $productIds): void
    {
        try {
            DB::transaction(function () use ($productIds) {
                // Lock the collection row to prevent concurrent modifications
                $collection = Collection::where('id', $this->collectionId)
                    ->where('merchant_id', $this->merchantId)
                    ->lockForUpdate()
                    ->first();

                if (!$collection) {
                    throw new Exception('Collection not found or deleted');
                }

                $removedCount = DB::table('collection_product')
                    ->where('collection_id', $collection->id)
                    ->whereIn('product_id', $productIds)
                    ->delete();

                if ($removedCount > 0) {
                    $collection->products_count = max(0, $collection->products_count - $removedCount);
                    $collection->save();

                    $this->logInfo('Removed products chunk', [
                        'collection_id' => $collection->id,
                        'removed_count' => $removedCount,
                    ]);
                }
            });
        } catch (Exception $e) {
            $this->logError('Failed to remove product chunk', [
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


    private function logError(string $message, array $context = []): void
    {
        Log::error($message, $this->buildLogContext($context));
    }


    private function buildLogContext(array $context): array
    {
        return array_merge($context, [
            'job' => 'BulkRemoveProductsFromCollectionJob',
            'merchant_id' => $this->merchantId,
        ]);
    }
}
