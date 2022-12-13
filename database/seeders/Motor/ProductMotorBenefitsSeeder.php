<?php

namespace Database\Seeders\Motor;

use App\Models\Motor\ProductBenefits;
use Illuminate\Database\Seeder;

class ProductMotorBenefitsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $benefits = [
            '10' => [
                'where' => 'Malaysia, Singapore, Brunei',
                'who' => 'Main driver and One (1) named driver (Optional :Additional named drivers)',
                'workshops' => '350 panel workshops nationwide',
                'mobile_accident_response' => [
                    'Jump start/flat battery replacement',
                    'Replace your tyres',
                    'Fuel delivery',
                    '24-hour towing assistance'
                ],
                'young_driver_excess' => 'Compulsory excess of RM 400',
                'excess' => 'NIL (except for cars above 2,000 cc, the excess applicable will be 1% on the sum insured)',
                'repair_warranty' => '3 Months',
            ],
            '15' => [
                'where' => 'Malaysia, Singapore, Brunei',
                'who' => 'Any authorised drivers',
                'workshops' => 'More than 500 authorised panel workshops nationwide',
                'mobile_accident_response' => [
                    'Jump start/flat battery replacement',
                    'Replace your tyres',
                    'Fuel delivery',
                    '24-hour towing assistance'
                ],
                'young_driver_excess' => 'Compulsory excess of RM 400',
                'excess' => 'NIL (except for young and inexperienced driver)',
                'repair_warranty' => '18 Months',
            ],
            '16' => [
                'where' => 'Malaysia, Singapore, Brunei',
                'who' => 'Any authorised drivers',
                'workshops' => 'More than 300 authorised panel workshops nationwide',
                'mobile_accident_response' => [
                    'Jump start/flat battery replacement',
                    'Replace your tyres',
                    'Fuel delivery',
                    '24-hour towing assistance'
                ],
                'young_driver_excess' => 'Compulsory excess of RM 400',
                'excess' => 'NIL (except for young and inexperienced driver)',
                'repair_warranty' => '12 Months',
            ]
        ];

        foreach($benefits as $product_id => $benefit) {
            ProductBenefits::updateOrCreate([
                'product_id' => $product_id
            ],[
                'benefits' => json_encode($benefit)
            ]);
        }
    }
}
