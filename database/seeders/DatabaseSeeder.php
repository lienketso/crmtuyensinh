<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Tạo tài khoản super admin mặc định
        User::updateOrCreate(
            ['email' => 'thanhan1507@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => '@Lks2026@',
                'role' => 'super_admin',
            ]
        );
    }
}
