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
                'name' => 'AmAssurance auto365 Comprehensive Plus',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 3,
                'name' => 'AmAssurance auto365 Comprehensive Premier',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 7,
                'name' => 'Allianz-Motor',
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
                'name' => 'Tune-Motor',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 10,
                'name' => 'AIG-Motor',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 2,
                'name' => 'Etiqa-Motor',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 6,
                'name' => 'RHB-Motor',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 9,
                'name' => 'POI-Motor',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 5,
                'name' => 'Zurich-Motor',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 1,
                'name' => 'Etiqa Takaful-Motor',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 4,
                'name' => 'Zurich Takaful-Motor',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 13,
                'name' => 'Lonpac - Motor Comprehensive',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 11,
                'name' => 'Liberty-Motor',
                'active' => 1,
            ],
            [
                'product_type_id' => 2,
                'company_id' => 12,
                'name' => 'Berjaya Sompo - Motor Comprehensive',
                'active' => 1,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate([
                'company_id' => $product['company_id'],
                'name' => $product['name']
            ], $product);
        }
    }
}
