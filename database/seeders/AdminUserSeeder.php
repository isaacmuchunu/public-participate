<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@pps.ke'],
            [
                'name' => 'National Admin',
                'role' => 'admin',
                'county' => 'Nairobi',
                'is_verified' => true,
                'password' => Hash::make('kukus1993'),
            ]
        );
    }
}
