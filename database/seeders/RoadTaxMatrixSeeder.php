<?php

namespace Database\Seeders;

use App\Models\RoadTaxMatrix;
use Illuminate\Database\Seeder;

class RoadTaxMatrixSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $matrix = [
            // WM - Saloon [Individual Reg]
            [
                'registration_type' => 'Individual',
                'saloon' => true,
                'engine_capacity_from' => 0,
                'engine_capacity_to' => 1000,
                'region' => 'West',
                'base_rate' => 20,
                'progressive_rate' => 0,
            ],
            [
                'registration_type' => 'Individual',
                'saloon' => true,
                'engine_capacity_from' => 1001,
                'engine_capacity_to' => 1200,
                'region' => 'West',
                'base_rate' => 55,
                'progressive_rate' => 0,
            ],
            [
                'registration_type' => 'Individual',
                'saloon' => true,
                'engine_capacity_from' => 1201,
                'engine_capacity_to' => 1400,
                'region' => 'West',
                'base_rate' => 70,
                'progressive_rate' => 0,
            ],
            [
                'registration_type' => 'Individual',
                'saloon' => true,
                'engine_capacity_from' => 1401,
                'engine_capacity_to' => 1600,
                'region' => 'West',
                'base_rate' => 90,
                'progressive_rate' => 0,
            ],
            [
                'registration_type' => 'Individual',
                'saloon' => true,
                'engine_capacity_from' => 1601,
                'engine_capacity_to' => 1800,
                'region' => 'West',
                'base_rate' => 200,
                'progressive_rate' => 0.4,
            ],
            [
                'registration_type' => 'Individual',
                'saloon' => true,
                'engine_capacity_from' => 1801,
                'engine_capacity_to' => 2000,
                'region' => 'West',
                'base_rate' => 280,
                'progressive_rate' => 0.5,
            ],
            [
                'registration_type' => 'Individual',
                'saloon' => true,
                'engine_capacity_from' => 2001,
                'engine_capacity_to' => 2500,
                'region' => 'West',
                'base_rate' => 380,
                'progressive_rate' => 1,
            ],
            [
                'registration_type' => 'Individual',
                'saloon' => true,
                'engine_capacity_from' => 2501,
                'engine_capacity_to' => 3000,
                'region' => 'West',
                'base_rate' => 880,
                'progressive_rate' => 2.5,
            ],
            [
                'registration_type' => 'Individual',
                'saloon' => true,
                'engine_capacity_from' => 3001,
                'engine_capacity_to' => 0,
                'region' => 'West',
                'base_rate' => 2130,
                'progressive_rate' => 4.5,
            ],
            // WM - Saloon [Company Reg]
            [
                'registration_type' => 'Company',
                'saloon' => true,
                'engine_capacity_from' => 0,
                'engine_capacity_to' => 1000,
                'region' => 'West',
                'base_rate' => 20,
                'progressive_rate' => 0,
            ],
            [
                'registration_type' => 'Company',
                'saloon' => true,
                'engine_capacity_from' => 1001,
                'engine_capacity_to' => 1200,
                'region' => 'West',
                'base_rate' => 110,
                'progressive_rate' => 0,
            ],
            [
                'registration_type' => 'Company',
                'saloon' => true,
                'engine_capacity_from' => 1201,
                'engine_capacity_to' => 1400,
                'region' => 'West',
                'base_rate' => 140,
                'progressive_rate' => 0,
            ],
            [
                'registration_type' => 'Company',
                'saloon' => true,
                'engine_capacity_from' => 1401,
                'engine_capacity_to' => 1600,
                'region' => 'West',
                'base_rate' => 180,
                'progressive_rate' => 0,
            ],
            [
                'registration_type' => 'Company',
                'saloon' => true,
                'engine_capacity_from' => 1601,
                'engine_capacity_to' => 1800,
                'region' => 'West',
                'base_rate' => 400,
                'progressive_rate' => 0.8,
            ],
            [
                'registration_type' => 'Company',
                'saloon' => true,
                'engine_capacity_from' => 1801,
                'engine_capacity_to' => 2000,
                'region' => 'West',
                'base_rate' => 560,
                'progressive_rate' => 1,
            ],
            [
                'registration_type' => 'Company',
                'saloon' => true,
                'engine_capacity_from' => 2001,
                'engine_capacity_to' => 2500,
                'region' => 'West',
                'base_rate' => 760,
                'progressive_rate' => 3,
            ],
            [
                'registration_type' => 'Company',
                'saloon' => true,
                'engine_capacity_from' => 2501,
                'engine_capacity_to' => 3000,
                'region' => 'West',
                'base_rate' => 2260,
                'progressive_rate' => 7.5,
            ],
            [
                'registration_type' => 'Company',
                'saloon' => true,
                'engine_capacity_from' => 3001,
                'engine_capacity_to' => 0,
                'region' => 'West',
                'base_rate' => 6010,
                'progressive_rate' => 13.5,
            ],
            // WM - Non Saloon
            [
                'saloon' => false,
                'engine_capacity_from' => 0,
                'engine_capacity_to' => 1000,
                'region' => 'West',
                'base_rate' => 20,
                'progressive_rate' => 0,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 1001,
                'engine_capacity_to' => 1200,
                'region' => 'West',
                'base_rate' => 85,
                'progressive_rate' => 0,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 1201,
                'engine_capacity_to' => 1400,
                'region' => 'West',
                'base_rate' => 100,
                'progressive_rate' => 0,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 1401,
                'engine_capacity_to' => 1600,
                'region' => 'West',
                'base_rate' => 120,
                'progressive_rate' => 0,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 1601,
                'engine_capacity_to' => 1800,
                'region' => 'West',
                'base_rate' => 300,
                'progressive_rate' => 0.3,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 1801,
                'engine_capacity_to' => 2000,
                'region' => 'West',
                'base_rate' => 360,
                'progressive_rate' => 0.4,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 2001,
                'engine_capacity_to' => 2500,
                'region' => 'West',
                'base_rate' => 440,
                'progressive_rate' => 0.8,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 2501,
                'engine_capacity_to' => 3000,
                'region' => 'West',
                'base_rate' => 840,
                'progressive_rate' => 1.6,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 3001,
                'engine_capacity_to' => 0,
                'region' => 'West',
                'base_rate' => 1640,
                'progressive_rate' => 1.6,
            ],
            // EM - Saloon
            [
                'saloon' => true,
                'engine_capacity_from' => 0,
                'engine_capacity_to' => 1000,
                'region' => 'East',
                'base_rate' => 20,
                'progressive_rate' => 0,
            ],
            [
                'saloon' => true,
                'engine_capacity_from' => 1001,
                'engine_capacity_to' => 1200,
                'region' => 'East',
                'base_rate' => 44,
                'progressive_rate' => 0,
            ],
            [
                'saloon' => true,
                'engine_capacity_from' => 1201,
                'engine_capacity_to' => 1400,
                'region' => 'East',
                'base_rate' => 56,
                'progressive_rate' => 0,
            ],
            [
                'saloon' => true,
                'engine_capacity_from' => 1401,
                'engine_capacity_to' => 1600,
                'region' => 'East',
                'base_rate' => 72,
                'progressive_rate' => 0,
            ],
            [
                'saloon' => true,
                'engine_capacity_from' => 1601,
                'engine_capacity_to' => 1800,
                'region' => 'East',
                'base_rate' => 160,
                'progressive_rate' => 0.32,
            ],
            [
                'saloon' => true,
                'engine_capacity_from' => 1801,
                'engine_capacity_to' => 2000,
                'region' => 'East',
                'base_rate' => 224,
                'progressive_rate' => 0.25,
            ],
            [
                'saloon' => true,
                'engine_capacity_from' => 2001,
                'engine_capacity_to' => 2500,
                'region' => 'East',
                'base_rate' => 274,
                'progressive_rate' => 0.5,
            ],
            [
                'saloon' => true,
                'engine_capacity_from' => 2501,
                'engine_capacity_to' => 3000,
                'region' => 'East',
                'base_rate' => 524,
                'progressive_rate' => 1,
            ],
            [
                'saloon' => true,
                'engine_capacity_from' => 3001,
                'engine_capacity_to' => 0,
                'region' => 'East',
                'base_rate' => 1024,
                'progressive_rate' => 1.35,
            ],
            // EM - Non Saloon
            [
                'saloon' => false,
                'engine_capacity_from' => 0,
                'engine_capacity_to' => 1000,
                'region' => 'East',
                'base_rate' => 20,
                'progressive_rate' => 0,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 1001,
                'engine_capacity_to' => 1200,
                'region' => 'East',
                'base_rate' => 42.5,
                'progressive_rate' => 0,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 1201,
                'engine_capacity_to' => 1400,
                'region' => 'East',
                'base_rate' => 50,
                'progressive_rate' => 0,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 1401,
                'engine_capacity_to' => 1600,
                'region' => 'East',
                'base_rate' => 60,
                'progressive_rate' => 0,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 1601,
                'engine_capacity_to' => 1800,
                'region' => 'East',
                'base_rate' => 165,
                'progressive_rate' => 0.17,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 1801,
                'engine_capacity_to' => 2000,
                'region' => 'East',
                'base_rate' => 199,
                'progressive_rate' => 0.22,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 2001,
                'engine_capacity_to' => 2500,
                'region' => 'East',
                'base_rate' => 243,
                'progressive_rate' => 0.44,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 2501,
                'engine_capacity_to' => 3000,
                'region' => 'East',
                'base_rate' => 463,
                'progressive_rate' => 0.88,
            ],
            [
                'saloon' => false,
                'engine_capacity_from' => 3001,
                'engine_capacity_to' => 0,
                'region' => 'East',
                'base_rate' => 903,
                'progressive_rate' => 1.2,
            ],
        ];

        foreach($matrix as $value) {
            RoadTaxMatrix::create($value);
        }
    }
}
