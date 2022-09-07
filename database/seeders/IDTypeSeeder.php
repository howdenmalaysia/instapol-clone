<?php

namespace Database\Seeders;

use App\Models\IDType;
use Illuminate\Database\Seeder;

class IDTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            'New IC',
            'Passport',
            'Old IC',
            'Business Registration Number',
        ];

        foreach($types as $type) {
            IDType::updateOrCreate($type);
        }
    }
}
