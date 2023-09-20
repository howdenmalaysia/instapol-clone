<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(object $data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject((app()->environment('local', 'development') ? '[Dev] ' : '') . 'Order Confirmation #' . $this->data->insurance_code . ' - ' . $this->data->vehicle_number)
            ->view('backend.emails.payment_receipt')
            ->with((array) $this->data);
    }
}
