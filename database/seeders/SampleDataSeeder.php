<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\SuperAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $passwordHash = Hash::make('password');
        $now = now();

        Admin::query()->where('email', 'admin@cmu.edu.ph')->delete();
        SuperAdmin::query()->where('email', 'oarl.admin@cmu.edu.ph')->delete();

        SuperAdmin::updateOrCreate(
            ['email' => 'admin@cmu.edu.ph'],
            [
                'firstname' => 'Arlo',
                'lastname' => 'Super Admin',
                'email' => 'admin@cmu.edu.ph',
                'phone' => '09170000001',
                'password' => $passwordHash,
                'email_verified_at' => $now,
            ]
        );

        Admin::updateOrCreate(
            ['email' => 'oarl.admin@cmu.edu.ph'],
            [
                'firstname' => 'OARL',
                'lastname' => 'Admin',
                'email' => 'oarl.admin@cmu.edu.ph',
                'phone' => '09170000009',
                'role' => Admin::ROLE_ADMIN,
                'password' => $passwordHash,
                'email_verified_at' => $now,
            ]
        );

        Cache::forget('alumni-dashboard:payload');
        Cache::forget('alumni-dashboard:payload:v2');
        Cache::forget('admin-analytics:payload:v16');
        Cache::forget('admin-analytics:payload:v17');
    }

}
