<?php

use MiniLaravel\Database\Seeder;
use MiniLaravel\Database\DB;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => password_hash('password', PASSWORD_DEFAULT),
        ]);
    }
}
