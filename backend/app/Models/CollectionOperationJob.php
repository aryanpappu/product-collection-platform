<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollectionOperationJob extends Model
{
    protected $fillable = [
        'merchant_id',
        'collection_id',
        'operation_type',
        'filename',
        'file_path',
        'status',
        'total_rows',
        'processed_rows',
        'successful_rows',
        'failed_rows',
        'stock_added',
        'stock_removed',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(CollectionOperationLog::class);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }

    public function incrementProcessed(int $count = 1): void
    {
        $this->increment('processed_rows', $count);
    }

    public function incrementSuccessful(int $count = 1): void
    {
        $this->increment('successful_rows', $count);
    }

    public function incrementFailed(int $count = 1): void
    {
        $this->increment('failed_rows', $count);
    }

    public function addStockAmount(int $amount): void
    {
        $this->increment('stock_added', $amount);
    }

    public function removeStockAmount(int $amount): void
    {
        $this->increment('stock_removed', $amount);
    }

    public function getProgressPercentage(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return round(($this->processed_rows / $this->total_rows) * 100, 2);
    }
}
