<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoadtaxMatrixTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roadtax_matrix', function (Blueprint $table) {
            $table->id();

            $table->boolean('saloon')->default(true);
            $table->string('registration_type')->nullable();
            $table->integer('engine_capacity_from');
            $table->integer('engine_capacity_to');
            $table->string('region');
            $table->decimal('base_rate', 6);
            $table->decimal('progressive_rate', 4);

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
        Schema::dropIfExists('roadtax_matrix');
    }
}
