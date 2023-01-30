<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSumInsuredTypeToInsuranceMotorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurance_motors', function (Blueprint $table) {
            $table->string('sum_insured_type')->nullable()->after('market_value');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insurance_motors', function (Blueprint $table) {
            $table->dropColumn('sum_insured_type');
        });
    }
}
