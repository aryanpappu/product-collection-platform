<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportJob extends Model
{
    protected $fillable = [
        'merchant_id',
        'filename',
        'file_path',
        'total_rows',
        'processed_rows',
        'failed_rows',
        'successful_rows',
        'status',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'failed_rows' => 'integer',
        'successful_rows' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ImportJobLog::class);
    }

    public function getProgressPercentage(): int
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return (int)round(($this->processed_rows / $this->total_rows) * 100);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }
}
