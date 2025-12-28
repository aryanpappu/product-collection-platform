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
        Schema::create('collection_operation_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('collection_id')->constrained('collections')->onDelete('cascade');
            $table->string('operation_type'); // 'add' or 'remove'
            $table->string('filename');
            $table->string('file_path');
            $table->enum('status', ['queued', 'processing', 'completed', 'failed'])->default('queued');
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->integer('stock_added')->default(0);
            $table->integer('stock_removed')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('merchant_id');
            $table->index('collection_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_operation_jobs');
    }
};
