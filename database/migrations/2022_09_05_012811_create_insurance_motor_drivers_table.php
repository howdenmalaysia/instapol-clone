<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsuranceMotorDriversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insurance_motor_drivers', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('insurance_motor_id')->references('id')->on('insurance_motor');
            $table->string('name');
            $table->string('id_number');
            $table->tinyInteger('relationship_id');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('insurance_motor_drivers');
    }
}
