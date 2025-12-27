<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Add macro for common timestamp and tracking columns
        Blueprint::macro('commonColumns', function () {
            $this->timestamps();
            $this->timestamp('archived_at')->nullable();
            $this->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
        });


        Blueprint::macro('auditColumns', function () {
            $this->timestamps();
            $this->timestamp('archived_at')->nullable();
        });

        // indexes for better query performance
        Blueprint::macro('commonColumnsWithIndexes', function () {
            $this->timestamps();
            $this->timestamp('archived_at')->nullable()->index();
            $this->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $this->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();

            // indexes for frequently queried columns
            $this->index(['created_at', 'updated_at']);
        });
    }
}
