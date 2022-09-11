<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsuranceHoldersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insurance_holders', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('insurance_id')->references('id')->on('insurance');
            $table->string('name');
            $table->tinyInteger('id_type_id');
            $table->string('id_number');
            $table->string('nationality')->default('MAL');
            $table->date('date_of_birth');
            $table->tinyInteger('age');
            $table->enum('gender', ['M', 'F', 'O']);
            $table->string('phone_code')->default('60');
            $table->string('phone_number');
            $table->string('email_address');
            $table->string('occupation');
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
        Schema::dropIfExists('insurance_policy_holders');
    }
}
