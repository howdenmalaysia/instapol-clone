<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsuranceMotorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insurance_motors', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('insurance_id')->references('id')->on('insurance');
            $table->unsignedInteger('vehicle_state_id')->references('id')->on('states');
            $table->string('vehicle_number');
            $table->string('chassis_number');
            $table->string('engine_number');
            $table->string('make');
            $table->string('model');
            $table->tinyInteger('seating_capacity');
            $table->integer('engine_capacity');
            $table->integer('manufactured_year');
            $table->decimal('market_value');
            $table->string('nvic');
            $table->string('variant');
            $table->decimal('ncd_percantage', 4);
            $table->decimal('ncd_amount', 4);
            $table->decimal('previous_ncd_percentage', 4);
            $table->decimal('next_ncd_percentage', 4);
            $table->date('previous_inception_date');
            $table->date('previous_expiry_date');
            $table->date('previous_policy_expiry');
            $table->enum('disabled', ['N', 'Y'])->default('N');
            $table->enum('marital_status', ['S', 'D', 'O', 'M']);
            $table->tinyInteger('driving_experience');
            $table->decimal('loading');
            $table->tinyInteger('number_of_drivers');
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
        Schema::dropIfExists('insurance_motor');
    }
}
