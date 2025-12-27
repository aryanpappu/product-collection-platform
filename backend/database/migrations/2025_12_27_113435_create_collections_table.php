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
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('slug', 255);
            $table->boolean('is_active')->default(true);
            $table->integer('products_count')->default(0);

            $table->commonColumns();

            // Indexes for performance
            $table->index('merchant_id');
            $table->index('created_at');
            $table->unique(['merchant_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
