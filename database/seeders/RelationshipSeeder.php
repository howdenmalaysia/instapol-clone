<?php

namespace Database\Seeders;

use App\Models\Relationship;
use Illuminate\Database\Seeder;

class RelationshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $relationships = [
            'Parent / Parent In-Law',
            'Spouse',
            'Child',
            'Sibling / Relative',
            'Other',
        ];

        foreach($relationships as $relationship) {
            Relationship::create([
                'name' => $relationship
            ]);
        }
    }
}
