<?php

declare(strict_types=1);

use App\Core\RBAC\Models\Permission;
use App\Core\RBAC\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            [
                'name' => 'Consulter les thèmes',
                'slug' => 'themes.view',
                'group' => 'appearance',
                'description' => 'Consulter la liste et les détails des thèmes installés',
            ],
            [
                'name' => 'Gérer les thèmes',
                'slug' => 'themes.manage',
                'group' => 'appearance',
                'description' => 'Activer, désactiver, importer, supprimer des thèmes et publier leurs assets',
            ],
            [
                'name' => 'Personnaliser l’apparence',
                'slug' => 'appearance.customize',
                'group' => 'appearance',
                'description' => 'Personnaliser le CSS, le JavaScript et les balises head publiques',
            ],
        ];

        $created = [];
        foreach ($permissions as $perm) {
            $created[] = Permission::firstOrCreate(
                ['slug' => $perm['slug']],
                [
                    'name' => $perm['name'],
                    'group' => $perm['group'],
                    'description' => $perm['description'],
                ]
            );
        }

        // Assign to super_admin and admin roles if they exist
        /** @var Role|null $superAdmin */
        $superAdmin = Role::where('slug', 'super_admin')->first();
        if ($superAdmin !== null) {
            foreach ($created as $p) {
                if (! $superAdmin->permissions->contains('id', $p->id)) {
                    $superAdmin->permissions()->attach($p->id);
                }
            }
        }

        /** @var Role|null $admin */
        $admin = Role::where('slug', 'admin')->first();
        if ($admin !== null) {
            foreach ($created as $p) {
                if (! $admin->permissions->contains('id', $p->id)) {
                    $admin->permissions()->attach($p->id);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $slugs = ['themes.view', 'themes.manage', 'appearance.customize'];

        Permission::whereIn('slug', $slugs)->delete();
    }
};
