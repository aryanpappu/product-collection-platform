<?php

namespace App\Validators;

use Exception;

class ProductImportValidator
{
    private const REQUIRED_COLUMNS = ['sku', 'name', 'price'];
    private const OPTIONAL_COLUMNS = ['description', 'stock'];

    /**
     * Validate CSV header has required columns
     */
    public function validateHeader(array $header): void
    {
        $missingColumns = [];

        foreach (self::REQUIRED_COLUMNS as $column) {
            if (!in_array($column, $header)) {
                $missingColumns[] = $column;
            }
        }

        if (!empty($missingColumns)) {
            throw new Exception(
                'CSV missing required columns: ' . implode(', ', $missingColumns)
            );
        }
    }

    /**
     * Validate and sanitize a single product row
     */
    public function validateRow(array $row): array
    {
        $this->validateSku($row);
        $this->validateName($row);
        $this->validatePrice($row);

        return $this->sanitizeRow($row);
    }

    /**
     * Validate SKU field
     */
    private function validateSku(array $row): void
    {
        if (empty($row['sku'])) {
            throw new Exception('SKU is required');
        }
    }

    /**
     * Validate name field
     */
    private function validateName(array $row): void
    {
        if (empty($row['name'])) {
            throw new Exception('Name is required');
        }
    }

    /**
     * Validate price field
     */
    private function validatePrice(array $row): void
    {
        if (empty($row['price'])) {
            throw new Exception('Price is required');
        }

        if (!is_numeric($row['price'])) {
            throw new Exception('Price must be a valid number');
        }

        if ((float)$row['price'] < 0) {
            throw new Exception('Price must be positive');
        }
    }

    /**
     * Sanitize and normalize row data
     */
    private function sanitizeRow(array $row): array
    {
        return [
            'sku' => $this->sanitizeString($row['sku']),
            'name' => $this->sanitizeString($row['name']),
            'description' => $this->sanitizeDescription($row),
            'price' => $this->sanitizePrice($row['price']),
            'stock' => $this->sanitizeStock($row),
        ];
    }

    /**
     * Sanitize string field
     */
    private function sanitizeString(string $value): string
    {
        return trim($value);
    }

    /**
     * Sanitize description field
     */
    private function sanitizeDescription(array $row): ?string
    {
        if (empty($row['description'])) {
            return null;
        }

        return trim($row['description']);
    }

    /**
     * Sanitize price to float
     */
    private function sanitizePrice(string $price): float
    {
        return (float)$price;
    }

    /**
     * Sanitize stock to integer
     */
    private function sanitizeStock(array $row): int
    {
        if (empty($row['stock'])) {
            return 0;
        }

        if (!is_numeric($row['stock'])) {
            return 0;
        }

        return max(0, (int)$row['stock']);
    }
}
