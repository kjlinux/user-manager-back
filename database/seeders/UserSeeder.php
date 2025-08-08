<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Media;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'John',
            'email' => 'admin@nomail.com',
            'password' => bcrypt('admin'),
            'last_login_at' => now()
        ]);

        $user2 = User::create([
            'name' => 'Jane',
            'email' => 'user@nomail.com',
            'password' => bcrypt('admin'),
            'last_login_at' => now()
        ]);

        $user->assignRole('Administrateur');
        $user2->assignRole('Utilisateur');

        $filepath = 'photos/profile.jpg';

        Media::create([
            'file' => $filepath,
            'user_id' => $user->id,
        ]);

        Media::create([
            'file' => $filepath,
            'user_id' => $user2->id,
        ]);
    }
}
