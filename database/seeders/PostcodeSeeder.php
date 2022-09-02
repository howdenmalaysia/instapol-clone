<?php

namespace Database\Seeders;

use App\Models\Postcode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class PostcodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $file = file_get_contents('database/seeders/Postcodes.json');

        if($file) {
            $postcodes = json_decode($file);
    
            $rows = 0;
            foreach($postcodes as $postcode) {
                Postcode::create([
                    'postcode' => $postcode->postcode,
                    'state_id' => $postcode->state_id,
                    'area' => $postcode->area,
                    'post_office' => $postcode->post_office
                ]);
    
                $rows++;
            }

            Log::info("[PostcodeSeeder] Inserted {$rows} postcode data successfully");
        } else {
            Log::error("[PostcodeSeeder] Error occurred while inserting postcode data");
        }
    }
}
