<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Core\RBAC\Models\Permission;
use App\Core\RBAC\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RbacAndUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_roles_index_lists_system_roles(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();

        $response = $this->actingAs($admin)->get('/admin/roles');
        $response->assertOk();
        $response->assertSee('Super Administrateur');
        $response->assertSee('Administrateur');
        $response->assertSee('Éditeur');
    }

    public function test_can_create_custom_role_with_permissions(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        $permission = Permission::first();
        $this->assertNotNull($permission);

        $response = $this->actingAs($admin)->post('/admin/roles', [
            'name' => 'Gestionnaire RH',
            'slug' => 'hr_manager',
            'description' => 'Gestion du personnel',
            'permissions' => [$permission->id],
        ]);

        $response->assertRedirect('/admin/roles');

        $role = Role::where('slug', 'hr_manager')->first();
        $this->assertNotNull($role);
        $this->assertSame('Gestionnaire RH', $role->name);
        $this->assertFalse($role->is_system);
        $this->assertTrue($role->permissions->contains('id', $permission->id));
    }

    public function test_cannot_delete_system_role(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        $systemRole = Role::where('slug', 'super_admin')->first();
        $this->assertNotNull($systemRole);

        $response = $this->actingAs($admin)->delete("/admin/roles/{$systemRole->id}");
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
    }

    public function test_can_delete_custom_role(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        $customRole = Role::create([
            'name' => 'Role Temporaire',
            'slug' => 'role_temp',
            'is_system' => false,
        ]);

        $response = $this->actingAs($admin)->delete("/admin/roles/{$customRole->id}");
        $response->assertRedirect('/admin/roles');

        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);
    }

    public function test_users_index_lists_users(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['name' => 'Admin User']);

        $response = $this->actingAs($admin)->get('/admin/users');
        $response->assertOk();
        $response->assertSee('Admin User');
    }

    public function test_create_user_with_role(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        $editorRole = Role::where('slug', 'editor')->first();
        $this->assertNotNull($editorRole);

        $response = $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Nouvel Éditeur',
            'email' => 'editor@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'roles' => [$editorRole->id],
            'is_active' => '1',
        ]);

        $response->assertRedirect('/admin/users');

        $created = User::where('email', 'editor@example.com')->first();
        $this->assertNotNull($created);
        $this->assertTrue($created->hasRole('editor'));
        $this->assertTrue($created->is_active);
    }

    public function test_toggle_user_status_deactivates_and_revokes_sessions(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        /** @var User $target */
        $target = User::factory()->create(['is_active' => true]);

        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->insert([
                'id' => 'session_test_target',
                'user_id' => $target->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit',
                'payload' => 'dummy',
                'last_activity' => time(),
            ]);
        }

        $response = $this->actingAs($admin)->post("/admin/users/{$target->id}/toggle");
        $response->assertRedirect();

        $target->refresh();
        $this->assertFalse($target->is_active);

        if (Schema::hasTable('sessions')) {
            $this->assertSame(0, DB::table('sessions')->where('user_id', $target->id)->count());
        }
    }

    public function test_user_cannot_deactivate_themselves(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->post("/admin/users/{$admin->id}/toggle");
        $response->assertRedirect();
        $response->assertSessionHas('error');

        $admin->refresh();
        $this->assertTrue($admin->is_active);
    }

    public function test_admin_can_reset_2fa_for_user(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create();
        /** @var User $target */
        $target = User::factory()->create([
            'two_factor_secret' => Crypt::encryptString('DUMMYSECRET'),
            'two_factor_confirmed_at' => now(),
        ]);

        $this->assertTrue($target->hasTwoFactorEnabled());

        $response = $this->actingAs($admin)->post("/admin/users/{$target->id}/reset-2fa");
        $response->assertRedirect();

        $target->refresh();
        $this->assertFalse($target->hasTwoFactorEnabled());
        $this->assertNull($target->two_factor_secret);
    }
}
