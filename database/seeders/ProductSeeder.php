<?php

namespace Database\Seeders;

use App\Services\ProductStore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Fills the JSON product file with sample rows: php artisan db:seed --class=ProductSeeder
 */
class ProductSeeder extends Seeder
{
    public function run(ProductStore $store): void
    {
        $store->truncate();

        $samples = [
            ['product_name' => 'Wireless Mouse', 'quantity' => 25, 'price' => 19.99],
            ['product_name' => 'Mechanical Keyboard', 'quantity' => 10, 'price' => 89.50],
            ['product_name' => 'USB-C Cable', 'quantity' => 120, 'price' => 4.75],
        ];

        $now = Carbon::now();

        foreach ($samples as $index => $sample) {
            // Space the submissions out so the datetime ordering is visible.
            Carbon::setTestNow($now->copy()->subMinutes(count($samples) - $index));
            $store->create($sample);
        }

        Carbon::setTestNow();
    }
}
