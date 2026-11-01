<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Security\Audit\AuditLogger;
use App\Core\Security\Services\RecoveryCodeService;
use App\Core\Security\Services\TotpService;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class EnableTwoFactorCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'cms:security:enable-2fa {email? : The user email to activate 2FA for}';

    /**
     * @var string
     */
    protected $description = 'Enable two-factor authentication for an administrator via CLI';

    public function handle(
        TotpService $totpService,
        RecoveryCodeService $recoveryCodeService,
        AuditLogger $auditLogger
    ): int {
        $rawEmail = $this->argument('email');
        $email = is_string($rawEmail) ? trim($rawEmail) : '';

        if ($email === '') {
            $email = $this->ask('Enter the email address of the account', 'admin@lcms.local');
        }

        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("User not found with email: {$email}");

            return self::FAILURE;
        }

        $secret = $totpService->generateSecret();
        $recoveryCodes = $recoveryCodeService->generate();

        $user->two_factor_secret = Crypt::encryptString($secret);
        $user->two_factor_recovery_codes = $recoveryCodeService->encryptCodes($recoveryCodes);
        $user->two_factor_confirmed_at = now();
        $user->save();

        $auditLogger->log(
            action: 'security.2fa.enabled_cli',
            resourceType: 'User',
            resourceId: (string) $user->id,
            user: $user
        );

        $qrCodeUrl = $totpService->getQrCodeUrl(
            company: config('app.name', 'LCMS Core'),
            holder: (string) $user->email,
            secret: $secret
        );

        $this->newLine();
        $this->info("=== Two-Factor Authentication Enabled for: {$user->email} ===");
        $this->newLine();
        $this->line("TOTP Secret (Base32): <fg=yellow>{$secret}</>");
        $this->line("OTP URL: <fg=cyan>{$qrCodeUrl}</>");
        $this->newLine();
        $this->warn('--- EMERGENCY RECOVERY CODES ---');
        $this->line('Store these codes in a safe place. Each code can only be used once.');

        $rows = [];
        foreach (array_chunk($recoveryCodes, 2) as $pair) {
            $rows[] = [$pair[0], $pair[1] ?? ''];
        }
        $this->table(['Code 1', 'Code 2'], $rows);

        $this->info('Two-Factor Authentication is now ACTIVE on this account.');

        return self::SUCCESS;
    }
}
