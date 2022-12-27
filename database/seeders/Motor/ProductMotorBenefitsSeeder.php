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
            '3' => [
                'where' => 'Malaysia, Singapore, Brunei',
                'who' => 'Any authorised drivers',
                'workshops' => '350 panel workshops nationwide',
                'mobile_accident_response' => [
                    'Jump start/flat battery replacement',
                    'Replace your tyres',
                    'Fuel delivery',
                    '24-hour towing assistance'
                ],
                'young_driver_excess' => 'Compulsory excess of RM 400',
                'excess' => 'NIL (for driver above 21 years old only)',
                'repair_warranty' => '6 Months',
            ],
            '4' => [
                'where' => 'Malaysia, Singapore, Brunei',
                'who' => 'Any authorised drivers',
                'workshops' => 'More than 200 authorised panel workshops nationwide',
                'mobile_accident_response' => [
                    'Jump start/flat battery replacement',
                    'Replace your tyres',
                    'Fuel delivery',
                    '24-hour towing assistance'
                ],
                'young_driver_excess' => 'Compulsory excess of RM 400',
                'excess' => 'NIL (for driver above 21 years old only)',
                'repair_warranty' => '6 Months',
            ],
            '5' => [
                'where' => 'Malaysia, Singapore, Brunei',
                'who' => 'Any authorised drivers',
                'workshops' => 'More than 200 authorised panel workshops nationwide',
                'mobile_accident_response' => [
                    'Jump start/flat battery replacement',
                    'Replace your tyres',
                    'Fuel delivery',
                    '24-hour towing assistance'
                ],
                'young_driver_excess' => 'Compulsory excess of RM 400',
                'excess' => 'NIL (for driver above 21 years old only)',
                'repair_warranty' => '6 Months',
            ],
            '7' => [
                'where' => 'Malaysia, Singapore, Brunei',
                'who' => 'Main driver and One (1) Free Authorised Driver',
                'workshops' => '33 premier workshops and 453 panel workshops nationwide',
                'mobile_accident_response' => [
                    'Jump start/flat battery replacement',
                    'Replace your tyres',
                    'Fuel delivery',
                    '24-hour towing assistance'
                ],
                'young_driver_excess' => 'Compulsory excess of RM 400',
                'excess' => 'NIL (except for young and inexperienced driver)',
                'repair_warranty' => '6 Months',
            ],
            '8' => [
                'where' => 'Malaysia, Singapore, Brunei',
                'who' => 'Insured and all authorized driver(s), Passenger while travelling in the named vehicles',
                'workshops' => 'More than 300 authorised panel workshops nationwide',
                'mobile_accident_response' => [
                    'Jump start/flat battery replacement',
                    'Replace your tyres',
                    'Fuel delivery',
                    '24-hour towing assistance'
                ],
                'young_driver_excess' => 'Compulsory excess of RM 400',
                'excess' => 'NIL (for vehicle up to 2,000cc) (except for young and inexperienced driver)',
                'repair_warranty' => '6 Months',
            ],
            '10' => [
                'where' => 'Malaysia, Singapore, Brunei',
                'who' => 'Main driver and One (1) Free Authorised Driver',
                'workshops' => 'More than 200 authorised panel workshops nationwide',
                'mobile_accident_response' => [
                    'Jump start/flat battery replacement',
                    'Replace your tyres',
                    'Fuel delivery',
                    '24-hour towing assistance'
                ],
                'young_driver_excess' => 'Compulsory excess of RM 400',
                'excess' => 'NIL (except for young and inexperienced driver)',
                'repair_warranty' => '12 Months',
            ],
            '14' => [
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
                'excess' => 'NIL (except for vehicle less than 10 years old and less than 2,500 cc)',
                'repair_warranty' => '6 Months',
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
            ],
        ];

        foreach ($benefits as $product_id => $benefit) {
            ProductBenefits::updateOrCreate([
                'product_id' => $product_id
            ], [
                'benefits' => json_encode($benefit)
            ]);
        }
    }
}
