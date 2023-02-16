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
                'amount' => 8.00,
                'processing_fee' => 2.75,
            ],
            [
                'description' => 'East Malaysia',
                'amount' => 10.00,
                'processing_fee' => 2.75,
            ],
            [
                'description' => 'Klang Valley',
                'amount' => 6.00,
                'processing_fee' => 2.75,
            ],
            [
                'description' => 'Others',
                'amount' => 10.00,
                'processing_fee' => 2.75,
            ]
        ];

        foreach($types as $type) {
            RoadtaxDeliveryType::updateOrCreate($type);
        }
    }
}
