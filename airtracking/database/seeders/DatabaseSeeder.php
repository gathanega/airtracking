<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ParameterLimitSeeder::class);   // wajib: rentang normal
        // $this->call(DemoSeeder::class);          // opsional: alat dan data contoh
    }
}
