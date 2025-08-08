<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Role>
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'id' => Str::uuid(),
            'name' => $name,
            'code' => Str::slug($name),
            'guard_name' => 'api',
        ];
    }

    public function admin(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'Admin',
            'code' => 'admin',
        ]);
    }

    public function user(): static
    {
        return $this->state(fn(array $attributes) => [
            'name' => 'User',
            'code' => 'user',
        ]);
    }
}
