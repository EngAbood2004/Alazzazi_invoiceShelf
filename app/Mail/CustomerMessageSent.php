<?php

namespace App\Mail;

use App\Models\CustomerMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CustomerMessageSent extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CustomerMessage $customerMessage)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->customerMessage->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.customer-message',
        );
    }
}
