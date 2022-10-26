<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsuranceMotorRoadtaxTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insurance_motor_roadtax', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('insurance_motor_id')->references('id')->on('insurance_motor');
            $table->unsignedInteger('roadtax_delivery_region_id')->references('id')->on('roadtax_delivery_type');
            $table->decimal('roadtax_renewal_fee');
            $table->decimal('myeg_fee', 5);
            $table->decimal('e_service_fee', 5);
            $table->decimal('service_tax', 5);
            $table->tinyInteger('issued')->default(false);
            $table->string('tracking_code')->nullable();
            $table->decimal('admin_charge', 5)->nullable();
            $table->tinyInteger('success')->default(false);
            $table->tinyInteger('active')->default(true);
            $table->string('recipient_name');
            $table->string('recipient_phone_number');
            $table->string('recipient_address_one');
            $table->string('recipient_address_two');
            $table->string('recipient_postcode', 5);
            $table->string('recipient_city');
            $table->string('recipient_state');
            
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
        Schema::dropIfExists('insurance_motor_roadtax');
    }
}
