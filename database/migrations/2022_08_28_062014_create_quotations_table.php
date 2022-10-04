<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuotationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedInteger('product_type')->references('id')->on('product_type');
            $table->string('vehicle_number');
            $table->string('email_address');
            $table->json('request_param');
            $table->string('referrer')->nullable();
            $table->string('remarks')->nullable();
            $table->tinyInteger('active')->default(true);
            $table->tinyInteger('compare_page');

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
        Schema::dropIfExists('quotations');
    }
}
