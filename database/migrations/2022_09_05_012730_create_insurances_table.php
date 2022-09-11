<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInsurancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('insurances', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('product_id')->references('id')->on('products');
            $table->string('insurance_code')->nullable();
            $table->unsignedInteger('customer_id')->references('id')->on('customers');
            $table->tinyInteger('insurance_status');
            $table->string('referrer')->nullable();
            $table->date('inception_date');
            $table->date('expiry_date');
            $table->decimal('amount');
            $table->date('quotation_date');
            $table->enum('channel', ['online', 'manual'])->default('online');
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
        Schema::dropIfExists('insurances');
    }
}
