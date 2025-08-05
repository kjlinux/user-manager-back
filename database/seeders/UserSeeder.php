<?php

namespace Database\Seeders;

use App\Models\User;
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
    }
}
