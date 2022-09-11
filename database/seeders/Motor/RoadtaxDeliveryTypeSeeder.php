<?php

namespace Database\Seeders\Motor;

use App\Models\Motor\RoadtaxDeliveryType;
use Illuminate\Database\Seeder;

class RoadtaxDeliveryTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            [
                'description' => 'West Malaysia',
                'amount' => 10.00,
                'processing_fee' => 4.00,
            ],
            [
                'description' => 'East Malaysia',
                'amount' => 15.00,
                'processing_fee' => 9.00,
            ],
            [
                'description' => 'Klang Valley',
                'amount' => 0.00,
                'processing_fee' => 9.28,
            ],
            [
                'description' => 'Others',
                'amount' => 0.00,
                'processing_fee' => 11.40,
            ]
        ];

        foreach($types as $type) {
            RoadtaxDeliveryType::updateOrCreate($type);
        }
    }
}
