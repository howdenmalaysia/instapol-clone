<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('user_id')->references('id')->on('users');
            $table->unsignedInteger('type_id')->references('id')->on('customer_types');
            $table->enum('gender', ['M', 'F']);
            $table->string('name');
            $table->unsignedInteger('id_type_id')->references('id')->on('id_types');
            $table->string('id_number');
            $table->date('date_of_birth');
            $table->string('referral_code')->nullable();

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
        Schema::dropIfExists('customers');
    }
}
