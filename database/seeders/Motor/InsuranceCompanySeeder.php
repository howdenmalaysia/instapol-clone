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
                'bank_code' => 'Maybank Islamic Berhad',
                'bank_account_no' => '550510572306',
                'email_to' => 'indra.e@etiqa.com.my',
                'email_cc' => 'azmil.a@etiqa.com.my,yazid.b@etiqa.com.my'
            ],
            [
                'name' => 'Etiqa',
                'logo' => 'etiqa.png',
                'sequence' => 2,
                'active' => 1,
                'coming_soon' => 0,
                'bank_code' => 'Malayan Banking Berhad',
                'bank_account_no' => '500511770222',
                'email_to' => 'indra.e@etiqa.com.my',
                'email_cc' => 'azmil.a@etiqa.com.my,yazid.b@etiqa.com.my'
            ],
            [
                'name' => 'AmGeneral',
                'logo' => 'am.png',
                'sequence' => 3,
                'active' => 1,
                'coming_soon' => 0,
                'bank_code' => 'Maybank',
                'bank_account_no' => '512316305855',
                'email_to' => 'yong-chai-har@amgeneralinsurance.com,phiang-wei.lim@amgeneralinsurance.com',
                'email_cc' => 'amgeneral-broking-spst@amgeneralinsurance.com,norhaya-mustapa@amgeneralinsurance.com,geoffery-yeo@amgeneralinsurance.com'
            ],
            [
                'name' => 'Zurich General Takaful Malaysia Berhad',
                'logo' => 'zurich-takaful.png',
                'sequence' => 4,
                'active' => 1,
                'coming_soon' => 0,
                'bank_code' => 'Maybank Islamic Berhad',
                'bank_account_no' => '564016119094',
                'email_to' => 'mira.syaliana@zurich.com.my',
                'email_cc' => 'meiying.tang@zurich.com.my'
            ],
            [
                'name' => 'Zurich General Insurance Malaysia Berhad',
                'logo' => 'zurich.png',
                'sequence' => 5,
                'active' => 1,
                'coming_soon' => 0,
                'bank_code' => 'Standard Chartered Bank (M) Berhad',
                'bank_account_no' => '312143289665',
                'email_to' => 'mira.syaliana@zurich.com.my',
                'email_cc' => 'meiying.tang@zurich.com.my'
            ],
            [
                'name' => 'RHB',
                'logo' => 'rhb.png',
                'sequence' => 6,
                'active' => 1,
                'coming_soon' => 0,
                'bank_code' => 'RHB Bank Berhad',
                'bank_account_no' => '21201300056340',
                'email_to' => 'carmen.lee@rhbgroup.com',
                'email_cc' => 'nur.hazura@rhbgroup.com'
            ],
            [
                'name' => 'Allianz',
                'logo' => 'allianz.png',
                'sequence' => 7,
                'active' => 1,
                'coming_soon' => 0,
                'bank_code' => 'Citibank Berhad',
                'bank_account_no' => '7780000100000000',
                'email_to' => 'siti.kalimah@allianz.com.my',
                'email_cc' => 'chin.hueylee@allianz.com.my'
            ],
            [
                'name' => 'Tune Protect',
                'logo' => 'tuneprotect.png',
                'sequence' => 8,
                'active' => 1,
                'coming_soon' => 0,
                'bank_code' => 'Standard Chartered Bank (M) Berhad',
                'bank_account_no' => '312130126738',
                'email_to' => 'yeongchern.lo@tuneprotect.com,solomon.sia@tuneprotect.com,sitifatimah.kimi@tuneprotect.com',
                'email_cc' => 'sitibahiyah.badrin@tuneprotect.com,colcheng.yeoh@tuneprotect.com,hasnah.bebe@tuneprotect.com'
            ],
            [
                'name' => 'Pacific & Orient Insurance',
                'logo' => 'pacificorient.png',
                'sequence' => 9,
                'active' => 1,
                'coming_soon' => 0,
                'bank_code' => 'Malayan Banking Berhad',
                'bank_account_no' => '514347992011',
                'email_to' => 'angie@pacific-orient.com',
                'email_cc' => 'jennyang@pacific-orient.com'
            ],
            [
                'name' => 'AIG',
                'logo' => 'aig.png',
                'sequence' => 10,
                'active' => 1,
                'coming_soon' => 0,
                'bank_code' => 'HSBC Bank Malaysia Berhad',
                'bank_account_no' => '301295879101',
                'email_to' => 'CPS.KL@aig.com',
                'email_cc' => 'AIGMYACCBRKL@aig.com'
            ],
            [
                'name' => 'Liberty',
                'logo' => 'liberty.png',
                'sequence' => 11,
                'active' => 1,
                'coming_soon' => 0,
                'bank_code' => 'Malayan Banking Berhad',
                'bank_account_no' => '514299125590',
                'email_to' => 'telagar@libertyinsurance.com.my,norhanam@libertyinsurance.com.my,siew.kokheng@liberyinsurance.com.my',
                'email_cc' => ''
            ],
            [
                'name' => 'Berjaya Sompo',
                'logo' => 'sompo.png',
                'sequence' => 12,
                'active' => 1,
                'coming_soon' => 0,
                'bank_code' => 'Malayan Banking Berhad',
                'bank_account_no' => '514084510416',
                'email_to' => 'rosyatimah.selamat@bsompo.com.my,ylten@bsompo.com.my,wongpc@bsompo.com.my',
                'email_cc' => ''
            ],
            [
                'name' => 'Lonpac',
                'logo' => 'lonpac.png',
                'sequence' => 13,
                'active' => 1,
                'coming_soon' => 0,
                'bank_code' => 'CIMB Bank Berhad',
                'bank_account_no' => '98990000000156',
                'email_to' => 'yhchia@lonpac.com,calvinchui@lonpac.com',
                'email_cc' => 'mardiana@lonpac.com,mohdzaki@lonpac.com'
            ],
            [
                'name' => 'Tokio Marine',
                'logo' => 'tokiomania.png',
                'sequence' => 14,
                'active' => 1,
                'coming_soon' => 1,
                'bank_code' => '',
                'bank_account_no' => '',
                'email_to' => '',
                'email_cc' => ''
            ],
            [
                'name' => 'MPI Generali',
                'logo' => 'mpig.png',
                'sequence' => 15,
                'active' => 1,
                'coming_soon' => 1,
                'bank_code' => '',
                'bank_account_no' => '',
                'email_to' => '',
                'email_cc' => ''
            ]
        ];

        foreach($companies as $company) {
            InsuranceCompany::updateOrCreate([
                'name' => $company['name']
            ], $company);
        }
    }
}
