<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateInsuranceExtraCoverTypeIdNullableInInsuranceExtraCoversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurance_extra_covers', function (Blueprint $table) {
            $table->unsignedInteger('insurance_extra_cover_type_id')->references('id')->on('insurance_extra_cover_type')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insurance_extra_covers', function (Blueprint $table) {
            $table->unsignedInteger('insurance_extra_cover_type_id')->references('id')->on('insurance_extra_cover_type');
        });
    }
}
