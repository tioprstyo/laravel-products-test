<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's data.
     */
    public function run(): void
    {
        $this->call(ProductSeeder::class);
    }
}
