<?php

use App\Http\Controllers\Api\CollectionController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\ProductImportController;
use Illuminate\Support\Facades\Route;

// Public routes (no merchant context required)
Route::get('/merchants', [MerchantController::class, 'index']);
// Routes requiring merchant context (X-Merchant-ID header)
Route::middleware('merchant.context')->group(function () {
    Route::prefix('imports')->group(function () {
        Route::post('/', [ProductImportController::class, 'import']);
        Route::get('/', [ProductImportController::class, 'list']);
        Route::get('/{importJobId}', [ProductImportController::class, 'status']);
        Route::get('/{importJobId}/errors', [ProductImportController::class, 'errors']);
    });

    // Collection Routes
    Route::prefix('collections')->group(function () {
        Route::get('/', [CollectionController::class, 'index']);
        Route::post('/', [CollectionController::class, 'store']);
        Route::get('/{id}', [CollectionController::class, 'show']);
        Route::put('/{id}', [CollectionController::class, 'update']);
        Route::delete('/{id}', [CollectionController::class, 'destroy']);
        Route::post('/{id}/products/bulk-add', [CollectionController::class, 'bulkAddProducts']);
        Route::post('/{id}/products/bulk-remove', [CollectionController::class, 'bulkRemoveProducts']);
    });
});
