<?php

declare(strict_types=1);

namespace App\Core\Settings\Services;

use App\Core\Settings\Mail\SmtpTestMail;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailTesterService
{
    /**
     * Send a test email to verify SMTP configuration.
     *
     * @return array{success: bool, message: string}
     */
    public function sendTestMail(string $toEmail): array
    {
        try {
            Mail::to($toEmail)->send(new SmtpTestMail);

            return [
                'success' => true,
                'message' => 'E-mail de test envoyé avec succès à '.$toEmail,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Échec de l\'envoi : '.$e->getMessage(),
            ];
        }
    }
}
