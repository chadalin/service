<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PinCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pin;

    public function __construct($pin)
    {
        $this->pin = $pin;
    }

    public function build()
    {
        return $this->subject('PIN код для входа в AutoDoc AI')
                    ->view('emails.pin-code')
                    ->with(['pin' => $this->pin]);
    }
}