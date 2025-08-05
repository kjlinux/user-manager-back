<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissionObjects = [];
        $permissions = [
            ['name' => 'Voir les utiliateurs', 'code' => 'view-users'],
            ['name' => 'Créer des utilisateurs', 'code' => 'create-users'],
            ['name' => 'Modifier les utilisateurs', 'code' => 'edit-users'],
            ['name' => 'Activer/Désactiver les utilisateurs', 'code' => 'switch-users'],
            ['name' => 'Supprimer les utilisateurs', 'code' => 'delete-users'],
            ['name' => 'Restorer les utilisateurs', 'code' => 'restore-users'],
        ];

        foreach ($permissions as $permission) {
            $permissionObjects[$permission['code']] = Permission::create([
                'id' => Str::uuid(),
                'name' => $permission['name'],
                'code' => $permission['code'],
                'guard_name' => 'api'
            ]);
        }

        $roleData = [
            ['name' => 'Administrateur', 'code' => 'admin'],
            ['name' => 'Utilisateur', 'code' => 'user'],
        ];

        foreach ($roleData as $data) {
            $role = Role::create([
                'id' => Str::uuid(),
                'name' => $data['name'],
                'code' => $data['code'],
                'guard_name' => 'api'
            ]);

            switch ($data['code']) {
                case 'admin':
                    $role->givePermissionTo(array_values($permissionObjects));
                    break;

                case 'user':
                    $userPermissions = array_filter($permissionObjects, function ($p) {
                        return !in_array($p->code, [
                            'view-users',
                        ]);
                    });
                    $role->givePermissionTo(array_values($userPermissions));
                    break;
            }
        }
    }
}
