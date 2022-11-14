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
                'key_benefits' => [
                    'Free Towing / Road Assistance',
                    'Agreed value for cars up to 10 years',
                    'Acceptance for cars up to 20 years',
                    'Established brand since 1972'
                ],
                'basic_info' => [
                    'where' => 'Malaysia, Singapore, Brunei',
                    'who' => 'Main driver and One (1) named driver (Optional :Additional named drivers)',
                    'workshops' => '350 panel workshops nationwide',
                    'mobile_accident_response' => [
                        'Jump start/flat battery replacement',
                        'Replace your tyres',
                        'Fuel delivery',
                        '24-hour towing assistance'
                    ],
                    'roadside_assistance' => true,
                    'young_driver_excess' => 'Compulsory excess of RM 400',
                    'excess' => 'NIL (except for cars above 2,000 cc, the excess applicable will be 1% on the sum insured)',
                ],
                'loss_damage_cover' => [
                    'accident_fire_theft' => true,
                    'spray_painting' => false,
                    'car_accessories' => false,
                    'key_replacement' => false,
                    'belongings' => false,
                    'ncd_relief' => false,
                    'temporary_courtesy' => false,
                    'cart' => false,
                ],
                'assistance_repair' => [
                    'towing' => 'Up to RM 350',
                    'repair_warranty' => '3 Months',
                    'car_delivery' => false,
                    'transport_allowance' => false,
                    'compassionate_allowance' => false,
                ],
                'third_party' => [
                    'legal_representation' => 'Up to RM 2,000',
                    'death' => 'Unlimited',
                    'damage_property' => 'Up to RM 3,000,000'
                ]
            ],
            '15' => [
                'key_benefits' => [
                    'Free Towing / Road Assistance',
                    'Smart Key Replacement',
                    'Personal Accident',
                ],
                'basic_info' => [
                    'where' => 'Malaysia, Singapore, Brunei',
                    'who' => 'Any authorised drivers',
                    'workshops' => 'More than 500 authorised panel workshops nationwide',
                    'mobile_accident_response' => [
                        'Jump start/flat battery replacement',
                        'Replace your tyres',
                        'Fuel delivery',
                        '24-hour towing assistance'
                    ],
                    'roadside_assistance' => true,
                    'young_driver_excess' => 'Compulsory excess of RM 400',
                    'excess' => 'NIL (except for young and inexperienced driver)',
                ],
                'loss_damage_cover' => [
                    'accident_fire_theft' => true,
                    'spray_painting' => false,
                    'car_accessories' => false,
                    'key_replacement' => true,
                    'belongings' => false,
                    'ncd_relief' => false,
                    'temporary_courtesy' => false,
                    'cart' => false,
                ],
                'assistance_repair' => [
                    'towing' => 'Up to 150KM (Maximum 4 times per annum)',
                    'repair_warranty' => '18 Months',
                    'car_delivery' => false,
                    'transport_allowance' => false,
                    'compassionate_allowance' => false,
                ],
                'third_party' => [
                    'legal_representation' => 'Up to RM 2,000',
                    'death' => 'Unlimited',
                    'damage_property' => 'Up to RM 3,000,000'
                ]
            ],
            '16' => [
                'key_benefits' => [
                    'Free Towing / Road Assistance',
                    'Personal Accident',
                    'Any Athorized Driver',
                    'FREE Special Perils Coverage'
                ],
                'basic_info' => [
                    'where' => 'Malaysia, Singapore, Brunei',
                    'who' => 'Any authorised drivers',
                    'workshops' => 'More than 300 authorised panel workshops nationwide',
                    'mobile_accident_response' => [
                        'Jump start/flat battery replacement',
                        'Replace your tyres',
                        'Fuel delivery',
                        '24-hour towing assistance'
                    ],
                    'roadside_assistance' => true,
                    'young_driver_excess' => 'Compulsory excess of RM 400',
                    'excess' => 'NIL (except for young and inexperienced driver)',
                ],
                'loss_damage_cover' => [
                    'accident_fire_theft' => true,
                    'spray_painting' => false,
                    'car_accessories' => false,
                    'key_replacement' => false,
                    'belongings' => false,
                    'ncd_relief' => false,
                    'temporary_courtesy' => false,
                    'cart' => true,
                ],
                'assistance_repair' => [
                    'towing' => 'Up to RM 300',
                    'repair_warranty' => '12 Months',
                    'car_delivery' => false,
                    'transport_allowance' => false,
                    'compassionate_allowance' => false,
                ],
                'third_party' => [
                    'legal_representation' => 'Up to RM 2,000',
                    'death' => 'Unlimited',
                    'damage_property' => 'Up to RM 3,000,000'
                ]
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
