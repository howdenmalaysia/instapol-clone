<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRoadtaxRecipientDetailsNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurance_motor_roadtax', function (Blueprint $table) {
            $table->string('recipient_name')->nullable()->change();
            $table->string('recipient_phone_number')->nullable()->change();
            $table->string('recipient_address_one')->nullable()->change();
            $table->string('recipient_address_two')->nullable()->change();
            $table->string('recipient_postcode', 5)->nullable()->change();
            $table->string('recipient_city')->nullable()->change();
            $table->string('recipient_state')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insurance_motor_roadtax', function (Blueprint $table) {
            $table->string('recipient_name');
            $table->string('recipient_phone_number');
            $table->string('recipient_address_one');
            $table->string('recipient_address_two');
            $table->string('recipient_postcode', 5);
            $table->string('recipient_city');
            $table->string('recipient_state');
        });
    }
}
