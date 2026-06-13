<?php

namespace Database\Factories;

use App\Models\Admin;
use App\Models\SuperAdmin;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'firstname' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'phone' => fake()->optional()->numerify('09#########'),
            'sex' => 'Male',
            'department' => fake()->optional()->randomElement(['IT', 'Education', 'Business']),
            'degree' => fake()->optional()->randomElement(['BSIT', 'BSEd', 'BSBA']),
            'year_graduated' => fake()->optional()->numberBetween(2005, 2025),
            'employed_after_grad' => fake()->optional()->randomElement(['Yes', 'No']),
            'has_scholarship' => false,
            'scholarship_name' => null,
            'scholarship_year_awarded' => null,
            'scholarship_helped_get_job' => null,
            'password' => static::$password ??= Hash::make('password'),
            'is_admin' => false,
            'data_privacy_consent' => false,
            'data_privacy_consented_at' => null,
            'newsletter_opt_in' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
            'is_admin' => true,
        ])->afterCreating(fn (User $user) => $this->mirrorLegacyAdmin($user, Admin::ROLE_ADMIN));
    }

    /**
     * Indicate that the user is a super admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_SUPER_ADMIN,
            'is_admin' => true,
        ])->afterCreating(fn (User $user) => $this->mirrorLegacyAdmin($user, User::ROLE_SUPER_ADMIN));
    }

    protected function mirrorLegacyAdmin(User $user, string $role): void
    {
        if ($role === User::ROLE_SUPER_ADMIN) {
            if (! Schema::hasTable('super_admins')) {
                return;
            }

            SuperAdmin::query()->updateOrCreate(
                ['id' => $user->id],
                [
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'password' => $user->password,
                    'phone' => $user->phone,
                    'email_verified_at' => $user->email_verified_at,
                ]
            );

            return;
        }

        if (! Schema::hasTable('admins')) {
            return;
        }

        Admin::query()->updateOrCreate(
            ['id' => $user->id],
            [
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'password' => $user->password,
                'phone' => $user->phone,
                'role' => $role,
                'email_verified_at' => $user->email_verified_at,
            ]
        );
    }
}
