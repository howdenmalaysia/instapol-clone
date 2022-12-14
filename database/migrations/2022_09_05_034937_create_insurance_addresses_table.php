<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsuranceAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insurance_addresses', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('insurance_id')->references('id')->on('insurances');
            $table->string('unit_no')->nullable();
            $table->string('building_name')->nullable();
            $table->string('address_one');
            $table->string('address_two');
            $table->string('city');
            $table->string('state');
            $table->string('postcode', 5);
            $table->unsignedInteger('created_by')->references('id')->on('users');
            $table->unsignedInteger('updated_by')->references('id')->on('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('insurance_addresses');
    }
}
