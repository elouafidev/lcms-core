<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\Security\Services\TotpService;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GeneralSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_guest_is_redirected_when_accessing_general_settings(): void
    {
        $response = $this->get('/admin/settings/general');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_general_settings(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/settings/general');
        $response->assertOk();
        $response->assertSee('Paramètres généraux');
        $response->assertSee($user->email);
    }

    public function test_user_can_update_profile(): void
    {
        /** @var User $user */
        $user = User::factory()->create(['name' => 'Old Name', 'email' => 'old@example.com']);

        $response = $this->actingAs($user)->put('/admin/settings/general/profile', [
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@example.com', $user->email);
    }

    public function test_user_can_change_password_with_valid_current_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('CurrentPassword123!'),
        ]);

        $response = $this->actingAs($user)->put('/admin/settings/general/password', [
            'current_password' => 'CurrentPassword123!',
            'password' => 'NewSecretPassword456!',
            'password_confirmation' => 'NewSecretPassword456!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('NewSecretPassword456!', $user->password));
        $this->assertNotNull($user->last_password_change_at);
    }

    public function test_user_cannot_change_password_with_invalid_current_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('CurrentPassword123!'),
        ]);

        $response = $this->actingAs($user)->put('/admin/settings/general/password', [
            'current_password' => 'WrongPassword!',
            'password' => 'NewSecretPassword456!',
            'password_confirmation' => 'NewSecretPassword456!',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    }

    public function test_2fa_setup_returns_json_secret_and_svg(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/settings/general/2fa/setup');
        $response->assertOk();
        $response->assertJsonStructure(['secret', 'svg']);

        $this->assertNotEmpty($response->json('secret'));
        $this->assertNotEmpty($response->json('svg'));
        $this->assertSame($response->json('secret'), session('2fa_pending_secret'));
    }

    public function test_2fa_confirmation_with_valid_code_enables_2fa(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $totpService = app(TotpService::class);
        $secret = $totpService->generateSecret();

        // Calculate a valid code for right now
        $google2fa = new \PragmaRX\Google2FA\Google2FA;
        $validCode = $google2fa->getCurrentOtp($secret);

        $response = $this->actingAs($user)
            ->withSession(['2fa_pending_secret' => $secret])
            ->postJson('/admin/settings/general/2fa/confirm', [
                'code' => $validCode,
            ]);

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['recovery_codes']);

        $user->refresh();
        $this->assertTrue($user->hasTwoFactorEnabled());
        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertNotNull($user->two_factor_secret);
        $this->assertSame($secret, Crypt::decryptString($user->two_factor_secret));
    }

    public function test_2fa_can_be_disabled_with_valid_password(): void
    {
        /** @var User $user */
        $user = User::factory()->create([
            'password' => Hash::make('MyPassword123!'),
            'two_factor_secret' => Crypt::encryptString('DUMMYSECRET'),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->assertTrue($user->hasTwoFactorEnabled());

        $response = $this->actingAs($user)->post('/admin/settings/general/2fa/disable', [
            'confirm_password' => 'MyPassword123!',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertFalse($user->hasTwoFactorEnabled());
        $this->assertNull($user->two_factor_secret);
    }
}
