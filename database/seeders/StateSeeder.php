<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $states = [
            [
                'code' => 'JHR',
                'name' => 'Johor',
                'region' => 'West'
            ],
            [
                'code' => 'KDH',
                'name' => 'Kedah',
                'region' => 'West'
            ],
            [
                'code' => 'KTN',
                'name' => 'Kelantan',
                'region' => 'West'
            ],
            [
                'code' => 'KUL',
                'name' => 'Wilayah Persekutuan Kuala Lumpur',
                'region' => 'West'
            ],
            [
                'code' => 'LBN',
                'name' => 'Wilayah Persekutuan Labuan',
                'region' => 'East'
            ],
            [
                'code' => 'MLK',
                'name' => 'Melaka',
                'region' => 'West'
            ],
            [
                'code' => 'NSN',
                'name' => 'Negeri Sembilan',
                'region' => 'West'
            ],
            [
                'code' => 'PHG',
                'name' => 'Pahang',
                'region' => 'West'
            ],
            [
                'code' => 'PJY',
                'name' => 'Wilayah Persekutuan Putrajaya',
                'region' => 'West'
            ],
            [
                'code' => 'PLS',
                'name' => 'Perlis',
                'region' => 'West'
            ],
            [
                'code' => 'PNG',
                'name' => 'Pulau Pinang',
                'region' => 'West'
            ],
            [
                'code' => 'PRK',
                'name' => 'Perak',
                'region' => 'West'
            ],
            [
                'code' => 'SBH',
                'name' => 'Sabah',
                'region' => 'East'
            ],
            [
                'code' => 'SGR',
                'name' => 'Selangor',
                'region' => 'West'
            ],
            [
                'code' => 'SRW',
                'name' => 'Sarawak',
                'region' => 'East'
            ],
            [
                'code' => 'TRG',
                'name' => 'Terengganu',
                'region' => 'West'
            ]
        ];

        $rows = 0;
        foreach($states as $state) {
            State::create($state);

            $rows ++;
        }

        Log::info("[StateSeeder] Inserted {$rows} states data successfully.");
    }
}
