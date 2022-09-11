<?php

namespace Database\Seeders\Motor;

use App\Models\Motor\InsuranceCompany;
use Illuminate\Database\Seeder;

class InsuranceCompanySeeder extends Seeder
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
                'logo' => 'etiqa-takaful.png',
                'sequence' => 1,
                'active' => 1,
                'coming_soon' => 0,
            ],
            [
                'name' => 'Etiqa',
                'logo' => 'etiqa.png',
                'sequence' => 2,
                'active' => 1,
                'coming_soon' => 0,
            ],
            [
                'name' => 'AmGeneral',
                'logo' => 'am.png',
                'sequence' => 3,
                'active' => 1,
                'coming_soon' => 0,
            ],
            [
                'name' => 'Zurich Takaful',
                'logo' => 'zurich-takaful.png',
                'sequence' => 4,
                'active' => 1,
                'coming_soon' => 0,
            ],
            [
                'name' => 'Zurich',
                'logo' => 'zurich.png',
                'sequence' => 5,
                'active' => 1,
                'coming_soon' => 0,
            ],
            [
                'name' => 'RHB',
                'logo' => 'rhb.png',
                'sequence' => 6,
                'active' => 1,
                'coming_soon' => 0,
            ],
            [
                'name' => 'Allianz',
                'logo' => 'allianz.png',
                'sequence' => 7,
                'active' => 1,
                'coming_soon' => 0,
            ],
            [
                'name' => 'Tune Protect',
                'logo' => 'tuneprotect.png',
                'sequence' => 8,
                'active' => 1,
                'coming_soon' => 0,
            ],
            [
                'name' => 'Pacific & Orient Insurance',
                'logo' => 'pacificorient.png',
                'sequence' => 9,
                'active' => 1,
                'coming_soon' => 0,
            ],
            [
                'name' => 'AIG',
                'logo' => 'aig.png',
                'sequence' => 10,
                'active' => 1,
                'coming_soon' => 0,
            ],
            [
                'name' => 'Liberty',
                'logo' => 'liberty.png',
                'sequence' => 11,
                'active' => 1,
                'coming_soon' => 0,
            ],
            [
                'name' => 'Berjaya Sompo',
                'logo' => 'sompo.png',
                'sequence' => 12,
                'active' => 1,
                'coming_soon' => 0,
            ],
            [
                'name' => 'Lonpac',
                'logo' => 'lonpac.png',
                'sequence' => 13,
                'active' => 1,
                'coming_soon' => 0,
            ],
            [
                'name' => 'Tokio Marine',
                'logo' => 'tokiomania.png',
                'sequence' => 14,
                'active' => 1,
                'coming_soon' => 1,
            ],
            [
                'name' => 'MPIG',
                'logo' => 'mpig.png',
                'sequence' => 15,
                'active' => 1,
                'coming_soon' => 1,
            ]
        ];

        foreach($companies as $company) {
            InsuranceCompany::updateOrCreate([
                'name' => $company['name']
            ], $company);
        }
    }
}
