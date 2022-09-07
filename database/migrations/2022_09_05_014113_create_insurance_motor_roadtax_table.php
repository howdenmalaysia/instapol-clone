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
            $table->decimal('e_service_fee', 6);
            $table->decimal('tax', 6);
            $table->tinyInteger('issued')->default(false);
            $table->string('tracking_code')->nullable();
            $table->decimal('admin_charge', 6);
            $table->tinyInteger('success')->default(false);
            $table->tinyInteger('active')->default(true);
            
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
