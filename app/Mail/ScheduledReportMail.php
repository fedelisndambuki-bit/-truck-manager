<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $reportName,
        public string $frequency,
        public string $attachmentContent,
        public string $attachmentFilename,
        public string $attachmentMime
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Scheduled Report: {$this->reportName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.scheduled-report',
            with: [
                'reportName' => $this->reportName,
                'frequency' => $this->frequency,
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->attachmentContent, $this->attachmentFilename)
                ->withMime($this->attachmentMime),
        ];
    }
}
