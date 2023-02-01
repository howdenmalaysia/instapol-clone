<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InsurerSettlementMail extends Mailable
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
        $mail = $this->subject((app()->environment('local', 'development') ? '[Dev] ' : '') .  "[instaPol] Settlement Details for {$this->data['insurer_name']} {$this->data['start_date']}")
        ->view('backend.emails.insurer_settlement', $this->data);

        foreach($this->files as $file) {
            $mail->attachFromStorage($file);
        }

        return $mail;
    }
}
