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
        Schema::create('collection_operation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_operation_job_id')->constrained('collection_operation_jobs')->onDelete('cascade');
            $table->integer('row_number');
            $table->json('row_data');
            $table->string('error_type')->nullable(); // 'product_not_found', 'invalid_stock', 'validation_error', etc.
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('collection_operation_job_id');
            $table->index('error_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_operation_logs');
    }
};
