<?php

use Aksa\Database\Seeder;

class SawitSeeder extends Seeder
{
    public function run()
    {
        // Demo seeder: create a sample sawit record in storage for demo purposes
        $storageDir = __DIR__ . '/../../storage/';
        if (!is_dir($storageDir)) mkdir($storageDir, 0777, true);
        $file = $storageDir . 'sawit_sample.json';
        $sample = [
            ['id' => 1, 'luas' => '5000', 'status' => 'unlicensed', 'region' => 'papua'],
            ['id' => 2, 'luas' => '1200', 'status' => 'licensed', 'region' => 'sumatera']
        ];
        file_put_contents($file, json_encode($sample, JSON_PRETTY_PRINT));
    }
}
