<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsurancePromosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insurance_promos', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('insurance_id')->references('id')->on('insurances');
            $table->unsignedInteger('promo_id')->references('id')->on('promotions');
            $table->decimal('discount_amount', 6);

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
        Schema::dropIfExists('insurance_promos');
    }
}
