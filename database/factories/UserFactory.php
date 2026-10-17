<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'identifiant' => fake()->unique()->userName(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => Role::Secretariat,
            'actif' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function administrateur(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Administrateur,
        ]);
    }

    public function secretariat(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::Secretariat,
        ]);
    }

    public function inactif(): static
    {
        return $this->state(fn (array $attributes) => [
            'actif' => false,
        ]);
    }
}
