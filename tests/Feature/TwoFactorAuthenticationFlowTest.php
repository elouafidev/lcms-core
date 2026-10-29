<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Security\Services\RecoveryCodeService;
use App\Core\Security\Services\TotpService;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorAuthenticationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_standard_user_without_2fa_logs_in_directly(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Secret123!',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('Secret123!'),
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Secret123!',
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
        $response->assertSessionHasErrors(['email']);
    }

    public function test_user_with_2fa_is_redirected_to_challenge(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('Secret123!'),
            'is_active' => true,
            'two_factor_secret' => Crypt::encryptString('JBSWY3DPEHPK3PXP'),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'Secret123!',
        ]);

        $response->assertRedirect(route('2fa.challenge'));
        $this->assertGuest();
        $this->assertEquals($user->id, session('auth.2fa.user_id'));
    }

    public function test_guest_without_pending_2fa_session_cannot_access_challenge(): void
    {
        $response = $this->get(route('2fa.challenge'));
        $response->assertRedirect(route('login'));
    }

    public function test_challenge_with_invalid_totp_fails(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'is_active' => true,
            'two_factor_secret' => Crypt::encryptString('JBSWY3DPEHPK3PXP'),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->withSession(['auth.2fa.user_id' => $user->id])
            ->post(route('2fa.verify'), [
                'code' => '000000',
            ]);

        $response->assertSessionHasErrors(['code']);
        $this->assertGuest();
    }

    public function test_challenge_with_valid_totp_logs_in(): void
    {
        $secret = app(TotpService::class)->generateSecret();
        $google2fa = new Google2FA;
        $validCode = $google2fa->getCurrentOtp($secret);

        /** @var User $user */
        $user = User::factory()->create([
            'is_active' => true,
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->withSession(['auth.2fa.user_id' => $user->id])
            ->post(route('2fa.verify'), [
                'code' => $validCode,
            ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('auth.2fa.user_id'));
    }

    public function test_challenge_with_recovery_code_logs_in_and_consumes_code(): void
    {
        $recoveryService = app(RecoveryCodeService::class);
        $codes = ['RECOVERY-CODE-1', 'RECOVERY-CODE-2'];

        /** @var User $user */
        $user = User::factory()->create([
            'is_active' => true,
            'two_factor_secret' => Crypt::encryptString('DUMMYSECRET'),
            'two_factor_recovery_codes' => $recoveryService->encryptCodes($codes),
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->withSession(['auth.2fa.user_id' => $user->id])
            ->post(route('2fa.verify'), [
                'code' => 'RECOVERY-CODE-1',
            ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $remainingCodes = $recoveryService->decryptCodes($user->two_factor_recovery_codes);
        $this->assertCount(1, $remainingCodes);
        $this->assertSame(['RECOVERY-CODE-2'], $remainingCodes);
    }

    public function test_cli_commands_can_enable_and_disable_2fa(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['email' => 'cli-test@lcms.local']);

        $this->artisan('cms:security:enable-2fa', ['email' => $user->email])
            ->assertSuccessful();

        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());

        $this->artisan('cms:recovery:disable-2fa', ['email' => $user->email])
            ->assertSuccessful();

        $user->refresh();
        $this->assertFalse($user->hasTwoFactorEnabled());
    }
}
