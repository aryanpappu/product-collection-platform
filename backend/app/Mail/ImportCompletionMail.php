<?php

namespace App\Mail;

use App\Models\ImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ImportCompletionMail extends Mailable
{
    use Queueable, SerializesModels;

    public ImportJob $importJob;

    /**
     * Create a new message instance.
     */
    public function __construct(ImportJob $importJob)
    {
        $this->importJob = $importJob;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $status = $this->importJob->status === 'completed' ? 'Completed' : 'Failed';

        return new Envelope(
            subject: "Product Import {$status} - {$this->importJob->filename}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.import-completion',
            with: [
                'importJob' => $this->importJob,
                'merchantName' => $this->importJob->merchant->name,
                'isSuccess' => $this->importJob->status === 'completed',
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
