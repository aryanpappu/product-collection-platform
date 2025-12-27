<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJobLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'import_job_id',
        'row_number',
        'row_data',
        'error_type',
        'error_message',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }
}
