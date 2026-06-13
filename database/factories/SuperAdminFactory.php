<?php

namespace Database\Factories;

use App\Models\SuperAdmin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SuperAdmin>
 */
class SuperAdminFactory extends Factory
{
    protected $model = SuperAdmin::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => fake()->optional()->numerify('09#########'),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }
}
