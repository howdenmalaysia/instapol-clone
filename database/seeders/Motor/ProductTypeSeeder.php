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
                'name' => 'Travel',
                'commission' => 25.00
            ],
            [
                'name' => 'Motor',
                'commission' => 10.00
            ],
            [
                'name' => 'Enhanced Road Warrior',
                'commission' => 25.00
            ],
            [
                'name' => 'Bike',
                'commission' => 0.00
            ],
            [
                'name' => 'Covid-19',
                'commission' => 0.00
            ],
        ];

        foreach($product_types as $type) {
            ProductType::updateOrCreate($type);
        }
    }
}
