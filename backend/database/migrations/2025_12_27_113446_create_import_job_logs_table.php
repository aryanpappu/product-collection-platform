<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_job_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_job_id')->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->text('row_data')->nullable();
            $table->string('error_type')->nullable();
            $table->text('error_message');
            $table->timestamp('created_at');

            // Indexes for performance
            $table->index('import_job_id');
            $table->index(['import_job_id', 'error_type']);
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
