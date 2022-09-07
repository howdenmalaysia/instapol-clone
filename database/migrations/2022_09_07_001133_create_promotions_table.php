<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('product_type');
            $table->string('code');
            $table->string('description');
            $table->dateTime('valid_from');
            $table->dateTime('valid_to');
            $table->integer('use_count')->default(0);
            $table->integer('use_max')->nullable();
            $table->decimal('discount_amount')->nullable();
            $table->tinyInteger('discount_percentage')->nullable();
            $table->decimal('minimum_spend', 6)->default(0);
            $table->string('discount_target');
            $table->longText('allowed_domain')->nullable();
            $table->tinyInteger('restrict_domain')->default(false);

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
        Schema::dropIfExists('promotions');
    }
}
