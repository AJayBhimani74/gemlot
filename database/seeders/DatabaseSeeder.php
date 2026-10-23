<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@gemlot.test'],
            [
                'name' => 'Gemlot Admin',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
                'company_name' => 'Gemlot',
                'is_approved' => true,
            ]
        );
    }
}
