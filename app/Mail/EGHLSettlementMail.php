<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EGHLSettlementMail extends Mailable
{
    use Queueable, SerializesModels;
    public $attachment;
    public $start_date;
    public $end_date;
    public $messages;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $path, string $start_date, string $end_date, string $messages)
    {
        $this->attachment = $path;
        $this->start_date = $start_date;
        $this->end_date = $end_date;
        $this->messages = $messages;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject((app()->environment('local', 'development') ? '[Dev] ' : '') .  "[eGHL-Howden] Settlement for {$this->start_date} to {$this->end_date}")
            ->view('backend.emails.eghl_settlement')
            ->attachFromStorage($this->attachment);
    }
}
