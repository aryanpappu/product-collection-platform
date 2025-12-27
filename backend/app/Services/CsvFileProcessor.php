<?php

namespace App\Services;

use Exception;

class CsvFileProcessor
{
    private $fileHandle;
    private array $header = [];
    private int $currentRow = 0;

    /**
     * Open and validate CSV file
     */
    public function open(string $filePath): void
    {
        $this->validateFileExists($filePath);
        $this->openFile($filePath);
        $this->readAndValidateHeader();
    }

    /**
     * Get the CSV header
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * Count total rows in CSV (excluding header)
     */
    public function countRows(): int
    {
        $count = 0;

        while (fgetcsv($this->fileHandle) !== false) {
            $count++;
        }

        $this->resetToDataStart();

        return $count;
    }

    /**
     * Read next row from CSV
     */
    public function readRow(): ?array
    {
        $row = fgetcsv($this->fileHandle);

        if ($row === false) {
            return null;
        }

        $this->currentRow++;

        return [
            'number' => $this->currentRow,
            'data' => $this->combineRowWithHeader($row),
        ];
    }

    /**
     * Read multiple rows as a chunk
     */
    public function readChunk(int $size): array
    {
        $chunk = [];

        for ($i = 0; $i < $size; $i++) {
            $row = $this->readRow();

            if ($row === null) {
                break;
            }

            $chunk[] = $row;
        }

        return $chunk;
    }

    /**
     * Close the CSV file
     */
    public function close(): void
    {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }

    /**
     * Validate file exists
     */
    private function validateFileExists(string $filePath): void
    {
        if (!file_exists($filePath)) {
            $dirPath = dirname($filePath);
            $dirContents = is_dir($dirPath)
                ? scandir($dirPath)
                : 'Directory does not exist';

            throw new Exception(
                "CSV file not found: {$filePath}. Directory contents: " . json_encode($dirContents)
            );
        }
    }

    /**
     * Open the file handle
     */
    private function openFile(string $filePath): void
    {
        $this->fileHandle = fopen($filePath, 'r');

        if ($this->fileHandle === false) {
            throw new Exception("Failed to open CSV file: {$filePath}");
        }
    }

    /**
     * Read and validate header row
     */
    private function readAndValidateHeader(): void
    {
        $this->header = fgetcsv($this->fileHandle);

        if ($this->header === false || empty($this->header)) {
            $this->close();
            throw new Exception("CSV file has no header row");
        }
    }

    /**
     * Reset file pointer to start of data (after header)
     */
    private function resetToDataStart(): void
    {
        rewind($this->fileHandle);
        fgetcsv($this->fileHandle); // Skip header
        $this->currentRow = 0;
    }

    /**
     * Combine row data with header to create associative array
     */
    private function combineRowWithHeader(array $row): array
    {
        return array_combine($this->header, $row);
    }

    /**
     * Destructor to ensure file is closed
     */
    public function __destruct()
    {
        $this->close();
    }
}
