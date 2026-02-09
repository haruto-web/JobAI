<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'venandrewmirasol@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('admin12345'),
                'user_type' => 'admin',
            ]
        );
    }
}
