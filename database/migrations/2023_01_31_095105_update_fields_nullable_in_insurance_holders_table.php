<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFieldsNullableInInsuranceHoldersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurance_holders', function (Blueprint $table) {
            $table->dateTime('date_of_birth')->nullable()->change();
            $table->integer('age')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insurance_holders', function (Blueprint $table) {
            $table->dateTime('date_of_birth')->change();
            $table->integer('age')->change();
        });
    }
}
