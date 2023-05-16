<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HowdenSettlementMail extends Mailable
{
    use Queueable, SerializesModels;

    public $files;
    public $data;
    public $is_monthly;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $filenames, array $data, bool $is_monthly = false)
    {
        $this->files = $filenames;
        $this->data = $data;
        $this->is_monthly = $is_monthly;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->subject((app()->environment('local', 'development') ? '[Dev] ' : '') .  "[instaPol] " . $this->is_monthly ? 'Monthly' : '' . " Settlement Details for {$this->data['start_date']} to {$this->data['end_date']}")
        ->view('backend.emails.howden_settlement', $this->data);

        foreach($this->files as $file) {
            $mail->attachFromStorage($file);
        }

        return $mail;
    }
}
