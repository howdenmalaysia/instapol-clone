<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateDiscountTargetInPromotionsTable extends Migration
{
    public function __construct()
    {
        // Register Enum Type
         
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('promotions', function (Blueprint $table) {
            DB::statement("ALTER TABLE promotions CHANGE COLUMN discount_target discount_target ENUM('basic_premium', 'gross_premium', 'service_tax', 'stamp_duty', 'road_tax', 'total_payable') DEFAULT NULL");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->string('discount_target')->change();
        });
    }
}
