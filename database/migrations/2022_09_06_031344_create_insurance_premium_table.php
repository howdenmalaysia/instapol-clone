<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsurancePremiumTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insurance_premium', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('insurance_id')->references('id')->on('insurances');
            $table->decimal('basic_premium', 6);
            $table->decimal('gross_premium', 6);
            $table->decimal('act_premium', 6);
            $table->decimal('net_premium', 6);
            $table->decimal('service_tax_amount', 5);
            $table->decimal('stamp_duty')->default(10.00);
            $table->decimal('total_premium', 6);
            $table->string('remarks')->nullable();

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
        Schema::dropIfExists('insurance_premium');
    }
}
