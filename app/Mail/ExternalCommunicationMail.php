<?php

namespace App\Mail;

use App\Models\CommunicationMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExternalCommunicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CommunicationMessage $communicationMessage)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->communicationMessage->subject ?: config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.external-communication');
    }
}
