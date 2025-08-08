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

        $roleData = [
            ['name' => 'Administrateur', 'code' => 'admin'],
            ['name' => 'Utilisateur', 'code' => 'user'],
        ];

        foreach ($roleData as $data) {
            Role::create([
                'id' => Str::uuid(),
                'name' => $data['name'],
                'code' => $data['code'],
                'guard_name' => 'api'
            ]);
        }
    }
}
