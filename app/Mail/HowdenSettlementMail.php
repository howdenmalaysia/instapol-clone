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

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(array $filenames, array $data)
    {
        $this->files = $filenames;
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->subject((app()->environment('local', 'development') ? '[Dev] ' : '') .  "[instaPol] Settlement Details for {$this->data['start_date']}")
        ->view('backend.emails.howden_settlement', $this->data);

        foreach($this->files as $file) {
            $mail->attachFromStorage($file);
        }

        return $mail;
    }
}
