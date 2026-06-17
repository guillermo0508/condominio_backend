<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@condominio.com'],
            [
                'name' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'is_admin' => true,
            ]
        );
    }
}
