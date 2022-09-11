<?php

namespace Database\Seeders\Motor;

use App\Models\Motor\VehicleBodyType;
use Illuminate\Database\Seeder;

class VehicleBodyTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = ['Saloon', 'Pickup', 'Coupe', 'Hatchback', 'MPV', 'SUV'];

        foreach($types as $type) {
            VehicleBodyType::create([
                'name' => $type
            ]);
        }
    }
}
