<?php

class DatabaseSeeder
{
    public function run()
    {
        // Call other seeders
        if (class_exists('UserSeeder')) {
            $seeder = new UserSeeder();
            $seeder->run();
        }
    }
}
