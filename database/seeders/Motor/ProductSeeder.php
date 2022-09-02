<?php

namespace Database\Seeders\Motor;

use App\Models\Motor\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $products = [
            [
                'product_type_id' => 2,
                'company_id' => 3,
                'name' => 'auto365 Comprehensive',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 3,
                'name' => 'auto365 Comprehensive Plus',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 3,
                'name' => 'auto365 Comprehensive Premier',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 7,
                'name' => 'Motor Comprehensive',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 14,
                'name' => 'Motor Comprehensive',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 8,
                'name' => 'Motor Easy',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 10,
                'name' => 'Motor Comprehensive',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 2,
                'name' => 'Motor Comprehensive',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 6,
                'name' => 'Motor Comprehensive',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 9,
                'name' => 'Motor Comprehensive',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 5,
                'name' => 'Z-Driver',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 1,
                'name' => 'Motor Comprehensive',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 4,
                'name' => 'Z-Driver',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 13,
                'name' => 'Private Car Secure',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 11,
                'name' => 'EZY Plus - Comprehensive',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 12,
                'name' => 'SOMPO Motor - Comprehensive',
                'active' => 1,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate($product);
        }
    }
}
