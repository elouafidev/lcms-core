<?php

declare(strict_types=1);

namespace App\Core\Settings\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SmtpTestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->subject('[LCMS Core] Test de configuration du serveur SMTP')
            ->html('<p>Ceci est un e-mail de test envoyé depuis LCMS Core pour valider la configuration de votre serveur SMTP.</p>');
    }
}
