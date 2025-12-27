<?php

use App\Http\Controllers\Api\ProductImportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Routes requiring merchant context (X-Merchant-ID header)
Route::middleware('merchant.context')->group(function () {
    // Product Import Routes
    Route::prefix('imports')->group(function () {
        Route::post('/', [ProductImportController::class, 'import']); // Upload CSV
        Route::get('/', [ProductImportController::class, 'list']); // List all imports
        Route::get('/{importJobId}', [ProductImportController::class, 'status']); // Get import status
        Route::get('/{importJobId}/errors', [ProductImportController::class, 'errors']); // Get import errors
    });
});
