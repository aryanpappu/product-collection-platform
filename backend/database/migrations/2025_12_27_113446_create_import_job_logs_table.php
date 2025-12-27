<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_job_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_job_id')->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->text('row_data')->nullable(); // Store failed row data for debugging
            $table->string('error_type')->nullable(); // validation, database, etc.
            $table->text('error_message');
            $table->timestamp('created_at');

            // Indexes for performance
            $table->index('import_job_id'); // Fast lookup of all errors for a job
            $table->index(['import_job_id', 'error_type']); // Filter errors by type
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_job_logs');
    }
};
