<?php

namespace Database\Seeders\Motor;

use App\Models\Motor\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $companies = [
            [
                'name' => 'Etiqa Takaful',
                'logo' => 'etiqa-takaful',
                'sequence' => 1,
                'active' => 1,
            ],
            [
                'name' => 'Etiqa',
                'logo' => 'etiqa',
                'sequence' => 2,
                'active' => 1,
            ],
            [
                'name' => 'AmGeneral',
                'logo' => 'am',
                'sequence' => 3,
                'active' => 1,
            ],
            [
                'name' => 'Zurich Takaful',
                'logo' => 'zurich-takaful',
                'sequence' => 4,
                'active' => 1,
            ],
            [
                'name' => 'Zurich',
                'logo' => 'zurich',
                'sequence' => 5,
                'active' => 1,
            ],
            [
                'name' => 'RHB',
                'logo' => 'rhb',
                'sequence' => 6,
                'active' => 1,
            ],
            [
                'name' => 'Allianz',
                'logo' => 'allianz',
                'sequence' => 7,
                'active' => 1,
            ],
            [
                'name' => 'Tune Protect',
                'logo' => 'tune',
                'sequence' => 8,
                'active' => 1,
            ],
            [
                'name' => 'P&O Insurance',
                'logo' => 'pacificorient',
                'sequence' => 9,
                'active' => 1,
            ],
            [
                'name' => 'AIG',
                'logo' => 'aig',
                'sequence' => 10,
                'active' => 1,
            ],
            [
                'name' => 'Liberty',
                'logo' => 'liberty',
                'sequence' => 11,
                'active' => 1,
            ],
            [
                'name' => 'Berjaya Sompo',
                'logo' => 'sompo',
                'sequence' => 12,
                'active' => 1,
            ],
            [
                'name' => 'Lonpac',
                'logo' => 'lonpac',
                'sequence' => 13,
                'active' => 1,
            ],
            [
                'name' => 'Tokio Marine',
                'logo' => 'tokiomania',
                'sequence' => 14,
                'active' => 1,
            ],
            [
                'name' => 'MPIG',
                'logo' => 'mpig',
                'sequence' => 15,
                'active' => 1,
            ]
        ];

        foreach($companies as $company) {
            Company::updateOrCreate($company);
        }
    }
}
