<?php

declare(strict_types=1);

namespace Platform\Core\Http\Controllers\Admin;

use App\Core\Security\Audit\AuditLogger;
use App\Core\Security\Services\RecoveryCodeService;
use App\Core\Security\Services\TotpService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class GeneralSettingsController extends Controller
{
    /**
     * Display the authenticated user profile and security settings.
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $activeSessions = [];
        if (Schema::hasTable('sessions')) {
            $records = DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderBy('last_activity', 'desc')
                ->get();

            $currentSessionId = session()->getId();
            foreach ($records as $record) {
                $activeSessions[] = [
                    'id' => $record->id,
                    'ip_address' => $record->ip_address,
                    'user_agent' => $record->user_agent,
                    'is_current' => $record->id === $currentSessionId,
                    'last_active' => Carbon::createFromTimestamp($record->last_activity)->diffForHumans(),
                ];
            }
        }

        return view('core::admin.settings.general', [
            'user' => $user,
            'activeSessions' => $activeSessions,
        ]);
    }

    /**
     * Update authenticated user personal info.
     */
    public function updateProfile(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $oldValues = ['name' => $user->name, 'email' => $user->email];
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->save();

        $auditLogger->log(
            action: 'user.profile.updated',
            resourceType: 'User',
            resourceId: (string) $user->id,
            oldValues: $oldValues,
            newValues: ['name' => $user->name, 'email' => $user->email],
            user: $user
        );

        return back()->with('success', 'Vos informations de profil ont été mises à jour.');
    }

    /**
     * Update user password with current password verification.
     */
    public function updatePassword(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = Hash::make((string) $request->input('password'));
        $user->last_password_change_at = now();
        $user->save();

        $auditLogger->log(
            action: 'user.password.updated',
            resourceType: 'User',
            resourceId: (string) $user->id,
            user: $user
        );

        return back()->with('success', 'Votre mot de passe a été modifié avec succès.');
    }

    /**
     * Start 2FA setup by generating a TOTP secret and SVG QR Code.
     */
    public function setup2fa(TotpService $totpService): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $secret = $totpService->generateSecret();
        session(['2fa_pending_secret' => $secret]);

        $svg = $totpService->getQrCodeSvg(
            company: config('app.name', 'LCMS Core'),
            holder: (string) $user->email,
            secret: $secret,
            size: 190
        );

        return response()->json([
            'secret' => $secret,
            'svg' => $svg,
        ]);
    }

    /**
     * Confirm 2FA setup by validating the first TOTP code and issuing recovery codes.
     */
    public function confirm2fa(
        Request $request,
        TotpService $totpService,
        RecoveryCodeService $recoveryCodeService,
        AuditLogger $auditLogger
    ): JsonResponse {
        $secret = session('2fa_pending_secret');

        if (! is_string($secret) || empty($secret)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune configuration 2FA en attente. Veuillez relancer la procédure.',
            ], 422);
        }

        $code = trim((string) $request->input('code'));

        if (! $totpService->verify($secret, $code)) {
            return response()->json([
                'success' => false,
                'message' => 'Le code TOTP saisi est incorrect ou a expiré.',
            ], 422);
        }

        /** @var User $user */
        $user = Auth::user();

        $recoveryCodes = $recoveryCodeService->generate();

        $user->two_factor_secret = Crypt::encryptString($secret);
        $user->two_factor_recovery_codes = $recoveryCodeService->encryptCodes($recoveryCodes);
        $user->two_factor_confirmed_at = now();
        $user->save();

        session()->forget('2fa_pending_secret');

        $auditLogger->log(
            action: 'security.2fa.enabled',
            resourceType: 'User',
            resourceId: (string) $user->id,
            user: $user
        );

        return response()->json([
            'success' => true,
            'message' => 'L\'authentification à deux facteurs a été activée avec succès !',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Disable 2FA with current password confirmation.
     */
    public function disable2fa(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $request->validate([
            'confirm_password' => ['required', 'string', 'current_password'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = null;
        $user->two_factor_confirmed_at = null;
        $user->save();

        $auditLogger->log(
            action: 'security.2fa.disabled',
            resourceType: 'User',
            resourceId: (string) $user->id,
            user: $user
        );

        return back()->with('success', 'L\'authentification à deux facteurs a été désactivée.');
    }

    /**
     * Terminate all other browser sessions for this user.
     */
    public function logoutOtherDevices(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $request->validate([
            'password_logout' => ['required', 'string', 'current_password:password_logout'],
        ]);

        /** @var User $user */
        $user = Auth::user();

        Auth::logoutOtherDevices((string) $request->input('password_logout'));

        $auditLogger->log(
            action: 'user.sessions.revoked_others',
            resourceType: 'User',
            resourceId: (string) $user->id,
            user: $user
        );

        return back()->with('success', 'Toutes les autres sessions actives ont été déconnectées.');
    }
}
