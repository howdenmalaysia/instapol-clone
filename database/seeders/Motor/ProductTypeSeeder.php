<?php

namespace Database\Seeders\Motor;

use App\Models\Motor\ProductType;
use Illuminate\Database\Seeder;

class ProductTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $product_types = [
            [
                'description' => 'Travel',
                'commission' => 25.00
            ],
            [
                'description' => 'Motor',
                'commission' => 10.00
            ],
            [
                'description' => 'Enhanced Road Warrior',
                'commission' => 25.00
            ],
            [
                'description' => 'Bike',
                'commission' => 0.00
            ],
            [
                'description' => 'Covid-19',
                'commission' => 0.00
            ],
        ];

        foreach($product_types as $type) {
            ProductType::updateOrCreate($type);
        }
    }
}
