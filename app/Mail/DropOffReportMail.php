<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DropOffReportMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $attachment;
    protected $batch;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $path, string $batch)
    {
        $this->attachment = $path;
        $this->batch = $batch;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject((app()->environment('local', 'development') ? '[Dev] ' : '') .  "Daily Drop-off Report for {$this->batch}")
            ->view('backend.emails.drop_off_report')
            ->with(['batch' => $this->batch])
            ->attachFromStorage($this->attachment);
    }
}
