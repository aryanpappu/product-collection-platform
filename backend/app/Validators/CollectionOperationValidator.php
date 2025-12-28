<?php

namespace App\Validators;

class CollectionOperationValidator
{
    private array $errors = [];

    /**
     * Validate a CSV row for collection operation
     * Required: (product_id OR sku) AND stock
     */
    public function validate(array $row, int $rowNumber): bool
    {
        $this->errors = [];

        if (empty($row['product_id']) && empty($row['sku'])) {
            $this->errors[] = "Row {$rowNumber}: Either 'product_id' or 'sku' is required";
            return false;
        }

        if (!empty($row['product_id']) && !$this->isValidId($row['product_id'])) {
            $this->errors[] = "Row {$rowNumber}: Invalid product_id format";
            return false;
        }

        if (!isset($row['stock'])) {
            $this->errors[] = "Row {$rowNumber}: 'stock' field is required";
            return false;
        }

        if (!$this->isValidStock($row['stock'])) {
            $this->errors[] = "Row {$rowNumber}: 'stock' must be a positive integer";
            return false;
        }

        return true;
    }

    public function sanitize(array $row): array
    {
        return [
            'product_id' => !empty($row['product_id']) ? (int)$row['product_id'] : null,
            'sku' => !empty($row['sku']) ? trim($row['sku']) : null,
            'stock' => (int)$row['stock'],
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    private function isValidId($value): bool
    {
        return is_numeric($value) && $value > 0;
    }

    private function isValidStock($value): bool
    {
        return is_numeric($value) && $value > 0;
    }

    public static function getRequiredHeaders(): array
    {
        return ['stock'];
    }

    public static function getOptionalHeaders(): array
    {
        return ['product_id', 'sku'];
    }

    public static function validateHeaders(array $headers): array
    {
        $errors = [];
        $headers = array_map('strtolower', array_map('trim', $headers));

        if (!in_array('stock', $headers)) {
            $errors[] = "Missing required column: 'stock'";
        }

        if (!in_array('product_id', $headers) && !in_array('sku', $headers)) {
            $errors[] = "At least one of 'product_id' or 'sku' column is required";
        }

        return $errors;
    }
}
