<?php

namespace Database\Seeders;

use App\Models\CustomerType;
use Illuminate\Database\Seeder;

class CustomerTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            'Customer',
            'Affiliate',
            'Tester',
            'Guest',
            'Agent',
            'Partner',
            'White Label',
        ];

        foreach($types as $type) {
            CustomerType::updateOrCreate($type);
        }
    }
}
