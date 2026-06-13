<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Admin>
 */
class AdminFactory extends Factory
{
    protected $model = Admin::class;

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
            'role' => Admin::ROLE_ADMIN,
            'remember_token' => Str::random(10),
        ];
    }

    public function alumniRecordsManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Admin::ROLE_ALUMNI_RECORDS_MANAGER,
        ]);
    }

    public function jobManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Admin::ROLE_JOB_MANAGER,
        ]);
    }
}
