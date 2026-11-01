<?php

declare(strict_types=1);

namespace App\Core\Console\Commands;

use App\Core\Security\Audit\AuditLogger;
use App\Models\User;
use Illuminate\Console\Command;

class DisableTwoFactorCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'cms:recovery:disable-2fa {email : The user email to disable 2FA for}';

    /**
     * @var string
     */
    protected $description = 'Emergency command to disable two-factor authentication for an account';

    public function handle(AuditLogger $auditLogger): int
    {
        $rawEmail = $this->argument('email');
        $email = is_string($rawEmail) ? trim($rawEmail) : '';

        /** @var User|null $user */
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $this->error("User not found with email: {$email}");

            return self::FAILURE;
        }

        if (! $user->hasTwoFactorEnabled()) {
            $this->warn("User {$email} does not have 2FA enabled.");

            return self::SUCCESS;
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        $auditLogger->log(
            action: 'security.2fa.disabled_cli_recovery',
            resourceType: 'User',
            resourceId: (string) $user->id,
            user: $user
        );

        $this->info("Two-Factor Authentication has been successfully DISABLED for {$email}.");

        return self::SUCCESS;
    }
}
