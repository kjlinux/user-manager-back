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
            'name' => 'Super',
            'email' => 'admin@nomail.com',
            'password' => bcrypt('admin'),
        ]);

        $user->assignRole('Administrateur');

        $filepath = 'photos/pic.jpg';

        Media::create([
            'file' => $filepath,
            'user_id' => $user->id,
        ]);
    }
}
