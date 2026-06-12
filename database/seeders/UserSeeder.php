<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Anton',
                'username' => 'anton',
                'email' => 'anton@example.com',
                'password' => bcrypt('12345678'),
                'role' => 'admin',
            ],
            [
                'name' => 'Rusdi',
                'username' => 'rusdi',
                'email' => 'rusdi@example.com',
                'password' => bcrypt('12345678'),
                'role' => 'team_lead',
            ],
            [
                'name' => 'Amba',
                'username' => 'amba',
                'email' => 'amba@example.com',
                'password' => bcrypt('12345678'),
                'role' => 'it_staff',
                'team' => 'programmer'
            ],
            [
                'name' => 'Gatot',
                'username' => 'gatot',
                'email' => 'gatot@example.com',
                'password' => bcrypt('12345678'),
                'role' => 'admin',
            ]
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
