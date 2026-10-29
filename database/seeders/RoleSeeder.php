<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\RBAC\Models\Permission;
use App\Core\RBAC\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Settings permissions
            ['name' => 'Voir paramètres généraux', 'slug' => 'settings.general.view', 'group' => 'settings', 'description' => 'Accéder aux paramètres du compte connecté'],
            ['name' => 'Gérer paramètres généraux', 'slug' => 'settings.general.manage', 'group' => 'settings', 'description' => 'Modifier les paramètres et la sécurité du compte'],
            ['name' => 'Voir paramètres du site', 'slug' => 'settings.site.view', 'group' => 'settings', 'description' => 'Consulter les paramètres du site et SMTP'],
            ['name' => 'Gérer paramètres du site', 'slug' => 'settings.site.manage', 'group' => 'settings', 'description' => 'Modifier la configuration du site et SMTP'],
            ['name' => 'Voir les rôles', 'slug' => 'settings.roles.view', 'group' => 'roles', 'description' => 'Consulter la liste des rôles et permissions'],
            ['name' => 'Gérer les rôles', 'slug' => 'settings.roles.manage', 'group' => 'roles', 'description' => 'Créer, modifier et assigner des rôles'],
            ['name' => 'Voir les comptes', 'slug' => 'settings.users.view', 'group' => 'users', 'description' => 'Consulter la liste des utilisateurs'],
            ['name' => 'Gérer les comptes', 'slug' => 'settings.users.manage', 'group' => 'users', 'description' => 'Créer, modifier, bloquer des utilisateurs'],
            ['name' => 'Voir les mises à jour', 'slug' => 'settings.update.view', 'group' => 'updates', 'description' => 'Consulter la version et l’historique des mises à jour'],
            ['name' => 'Gérer les mises à jour', 'slug' => 'settings.update.manage', 'group' => 'updates', 'description' => 'Appliquer des mises à jour du Core LCMS'],

            // Core plugins & audit permissions
            ['name' => 'Voir les plugins', 'slug' => 'plugins.view', 'group' => 'plugins', 'description' => 'Consulter la liste des plugins installés'],
            ['name' => 'Gérer les plugins', 'slug' => 'plugins.manage', 'group' => 'plugins', 'description' => 'Activer, désactiver ou installer des plugins'],
            ['name' => 'Consulter les journaux d’audit', 'slug' => 'audit.logs.view', 'group' => 'security', 'description' => 'Consulter l’historique des actions d’audit'],

            // Appearance & Themes permissions
            ['name' => 'Consulter les thèmes', 'slug' => 'themes.view', 'group' => 'appearance', 'description' => 'Consulter la liste et les détails des thèmes installés'],
            ['name' => 'Gérer les thèmes', 'slug' => 'themes.manage', 'group' => 'appearance', 'description' => 'Activer, désactiver, importer, supprimer des thèmes et publier leurs assets'],
            ['name' => 'Personnaliser l’apparence', 'slug' => 'appearance.customize', 'group' => 'appearance', 'description' => 'Personnaliser le CSS, le JavaScript et les balises head publiques'],
        ];

        $permissionModels = [];
        foreach ($permissions as $perm) {
            $permissionModels[$perm['slug']] = Permission::firstOrCreate(
                ['slug' => $perm['slug']],
                [
                    'name' => $perm['name'],
                    'group' => $perm['group'],
                    'description' => $perm['description'],
                ]
            );
        }

        // 1. Super Admin (is_system = true)
        $superAdmin = Role::firstOrCreate(
            ['slug' => 'super_admin'],
            [
                'name' => 'Super Administrateur',
                'description' => 'Accès complet et illimité au système et à la configuration',
                'is_system' => true,
            ]
        );
        // Super Admin gets all permissions
        $superAdmin->permissions()->sync(array_values(array_map(fn ($p) => $p->id, $permissionModels)));

        // 2. Admin (is_system = true)
        $admin = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Administrateur',
                'description' => 'Administration du CMS, gestion des comptes et configuration',
                'is_system' => true,
            ]
        );
        $adminPermissions = [
            'settings.general.view', 'settings.general.manage',
            'settings.site.view', 'settings.site.manage',
            'settings.roles.view',
            'settings.users.view', 'settings.users.manage',
            'settings.update.view',
            'plugins.view', 'plugins.manage',
            'themes.view', 'themes.manage', 'appearance.customize',
            'audit.logs.view',
        ];
        $admin->permissions()->sync(
            collect($adminPermissions)
                ->map(fn (string $slug) => $permissionModels[$slug]->id ?? null)
                ->filter()
                ->all()
        );

        // 3. Editor (is_system = true)
        $editor = Role::firstOrCreate(
            ['slug' => 'editor'],
            [
                'name' => 'Éditeur',
                'description' => 'Gestion des contenus et consultation de base',
                'is_system' => true,
            ]
        );
        $editorPermissions = [
            'settings.general.view', 'settings.general.manage',
        ];
        $editor->permissions()->sync(
            collect($editorPermissions)
                ->map(fn (string $slug) => $permissionModels[$slug]->id ?? null)
                ->filter()
                ->all()
        );

        // 4. User (is_system = false)
        $user = Role::firstOrCreate(
            ['slug' => 'user'],
            [
                'name' => 'Utilisateur',
                'description' => 'Utilisateur standard avec profil personnel',
                'is_system' => false,
            ]
        );
        $userPermissions = [
            'settings.general.view', 'settings.general.manage',
        ];
        $user->permissions()->sync(
            collect($userPermissions)
                ->map(fn (string $slug) => $permissionModels[$slug]->id ?? null)
                ->filter()
                ->all()
        );
    }
}
