<?php

namespace App\Services;

use App\Models\Product;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductBulkInsertService
{
    /**
     * Bulk insert products using INSERT IGNORE
     */
    public function bulkInsert(array $products): bool
    {
        if (empty($products)) {
            return true;
        }

        try {
            $this->executeBulkInsert($products);
            return true;
        } catch (Exception $e) {
            Log::error('Bulk insert failed', [
                'error' => $e->getMessage(),
                'count' => count($products),
            ]);
            return false;
        }
    }

    /**
     * Insert products one by one (fallback method)
     */
    public function insertOneByOne(array $products): array
    {
        $successful = 0;
        $failed = 0;

        foreach ($products as $productData) {
            if ($this->insertSingleProduct($productData)) {
                $successful++;
            } else {
                $failed++;
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
        ];
    }

    /**
     * Execute the bulk insert query
     */
    private function executeBulkInsert(array $products): void
    {
        $table = (new Product())->getTable();
        $columns = array_keys($products[0]);
        $valueSets = $this->prepareValueSets($products);

        $sql = sprintf(
            "INSERT IGNORE INTO %s (%s) VALUES %s",
            $table,
            implode(',', $columns),
            implode(',', $valueSets)
        );

        DB::statement($sql);
    }

    /**
     * Prepare value sets for bulk insert
     */
    private function prepareValueSets(array $products): array
    {
        $valueSets = [];

        foreach ($products as $product) {
            $valueSets[] = $this->prepareValueSet($product);
        }

        return $valueSets;
    }

    /**
     * Prepare a single value set
     */
    private function prepareValueSet(array $product): string
    {
        $values = array_map(
            fn($value) => $this->quoteValue($value),
            array_values($product)
        );

        return '(' . implode(',', $values) . ')';
    }

    /**
     * Quote a value for SQL
     */
    private function quoteValue($value): string
    {
        return DB::connection()->getPdo()->quote($value);
    }

    /**
     * Insert a single product
     */
    private function insertSingleProduct(array $productData): bool
    {
        try {
            Product::updateOrCreate(
                [
                    'merchant_id' => $productData['merchant_id'],
                    'sku' => $productData['sku'],
                ],
                $productData
            );
            return true;
        } catch (Exception $e) {
            Log::error('Single product insert failed', [
                'sku' => $productData['sku'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
