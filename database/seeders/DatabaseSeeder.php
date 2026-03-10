<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email' => env('SEED_ADMIN_EMAIL'),
            ],
            [
                'name'     => env('SEED_ADMIN_NAME'),
                'password' => Hash::make(env('SEED_ADMIN_PASSWORD')),
                'role'     => 'admin',
            ]
        );
    }
}