<?php

declare(strict_types=1);

namespace Plugins\Newsletter\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Plugins\Newsletter\Models\Subscriber;

class ConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscriber $subscriber,
        public string $confirmationUrl
    ) {}

    public function envelope(): Envelope
    {
        $appName = (string) config('app.name', 'LCMS Core');

        return new Envelope(
            subject: 'Confirmez votre inscription à la newsletter — '.$appName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'newsletter::emails.confirmation',
        );
    }
}
