<?php

namespace App\Listeners;

use App\Events\ImportCompleted;
use App\Mail\ImportCompletionMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendImportCompletionEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     * @throws \Exception
     */
    public function handle(ImportCompleted $event): void
    {
        $importJob = $event->importJob;
        $merchant = $importJob->merchant;

        try {
            Mail::to($merchant->email)->send(new ImportCompletionMail($importJob));

            Log::info('Import completion email sent', [
                'import_job_id' => $importJob->id,
                'merchant_id' => $merchant->id,
                'merchant_email' => $merchant->email,
                'status' => $importJob->status,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send import completion email', [
                'import_job_id' => $importJob->id,
                'merchant_id' => $merchant->id,
                'merchant_email' => $merchant->email,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(ImportCompleted $event, \Throwable $exception): void
    {
        Log::error('Import completion email listener failed', [
            'import_job_id' => $event->importJob->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
