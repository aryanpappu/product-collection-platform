<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionOperationLog extends Model
{
    protected $fillable = [
        'collection_operation_job_id',
        'row_number',
        'row_data',
        'error_type',
        'error_message',
    ];

    protected $casts = [
        'row_data' => 'array',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(CollectionOperationJob::class, 'collection_operation_job_id');
    }
}
