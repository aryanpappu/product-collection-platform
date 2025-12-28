<?php

namespace App\Services;

use App\Validators\CollectionOperationValidator;
use Generator;

class CollectionCsvProcessor
{
    private $fileHandle;
    private array $headers = [];
    private int $currentRow = 0;

    public function __construct(
        private string                       $filePath,
        private CollectionOperationValidator $validator
    )
    {
    }

    /**
     * Open and validate the CSV file
     * @throws \Exception
     */
    public function open(): void
    {
        if (!file_exists($this->filePath)) {
            throw new \Exception("File not found: {$this->filePath}");
        }

        $this->fileHandle = fopen($this->filePath, 'r');

        if ($this->fileHandle === false) {
            throw new \Exception("Failed to open file: {$this->filePath}");
        }


        $this->headers = fgetcsv($this->fileHandle);

        if ($this->headers === false) {
            throw new \Exception("Failed to read CSV headers");
        }


        $this->headers = array_map('strtolower', array_map('trim', $this->headers));


        $headerErrors = CollectionOperationValidator::validateHeaders($this->headers);

        if (!empty($headerErrors)) {
            throw new \Exception("CSV header validation failed: " . implode(', ', $headerErrors));
        }

        $this->currentRow = 1;
    }

    /**
     * Read CSV data in chunks
     */
    public function readChunk(int $chunkSize = 500): Generator
    {
        $chunk = [];

        while (($row = fgetcsv($this->fileHandle)) !== false) {
            $this->currentRow++;

            $data = array_combine($this->headers, $row);

            $chunk[] = [
                'row_number' => $this->currentRow,
                'data' => $data,
            ];

            if (count($chunk) >= $chunkSize) {
                yield $chunk;
                $chunk = [];
            }
        }

        if (!empty($chunk)) {
            yield $chunk;
        }
    }

    public function countRows(): int
    {
        $count = 0;
        $handle = fopen($this->filePath, 'r');

        fgetcsv($handle);

        while (fgetcsv($handle) !== false) {
            $count++;
        }

        fclose($handle);

        return $count;
    }

    public function close(): void
    {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
        }
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

}
