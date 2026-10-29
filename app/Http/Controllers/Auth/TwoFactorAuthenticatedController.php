<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Core\Security\Audit\AuditLogger;
use App\Core\Security\Services\RecoveryCodeService;
use App\Core\Security\Services\TotpService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class TwoFactorAuthenticatedController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Show the two-factor authentication challenge view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('auth.2fa.user_id')) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    /**
     * Verify the two-factor authentication code or recovery code.
     */
    public function store(
        Request $request,
        TotpService $totpService,
        RecoveryCodeService $recoveryCodeService,
        AuditLogger $auditLogger
    ): RedirectResponse {
        $userId = $request->session()->get('auth.2fa.user_id');

        if (! $userId) {
            return redirect()->route('login');
        }

        $throttleKey = '2fa_attempts_'.$userId.'_'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'code' => "Trop de tentatives échouées. Veuillez réessayer dans {$seconds} secondes.",
            ]);
        }

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        /** @var User|null $user */
        $user = User::find($userId);

        if ($user === null || ! $user->hasTwoFactorEnabled() || $user->two_factor_secret === null) {
            $request->session()->forget(['auth.2fa.user_id', 'auth.2fa.remember']);

            return redirect()->route('login');
        }

        $code = trim((string) $request->input('code'));
        $isValid = false;
        $usedRecoveryCode = false;

        // 1. Check TOTP Code
        try {
            $secret = Crypt::decryptString($user->two_factor_secret);
            if ($totpService->verify($secret, $code)) {
                $isValid = true;
            }
        } catch (Throwable) {
            $isValid = false;
        }

        // 2. Check Recovery Code if TOTP failed
        if (! $isValid) {
            $remaining = $recoveryCodeService->verifyAndConsume($code, $user->two_factor_recovery_codes);
            if ($remaining !== null) {
                $user->two_factor_recovery_codes = $recoveryCodeService->encryptCodes($remaining);
                $user->save();
                $isValid = true;
                $usedRecoveryCode = true;

                $auditLogger->log(
                    action: 'security.2fa.recovery_code_used',
                    resourceType: 'User',
                    resourceId: (string) $user->id,
                    user: $user
                );
            }
        }

        if (! $isValid) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'code' => 'Le code saisi est invalide ou a expiré.',
            ]);
        }

        RateLimiter::clear($throttleKey);

        $remember = (bool) $request->session()->pull('auth.2fa.remember', false);
        $request->session()->forget('auth.2fa.user_id');

        $user->last_login_at = now();
        $user->last_login_ip = $request->ip();
        $user->save();

        Auth::login($user, $remember);
        $request->session()->regenerate();

        $auditLogger->log(
            action: $usedRecoveryCode ? 'auth.login.2fa_recovery' : 'auth.login.2fa_totp',
            resourceType: 'User',
            resourceId: (string) $user->id,
            user: $user
        );

        return redirect()->intended(route('admin.dashboard'));
    }
}
