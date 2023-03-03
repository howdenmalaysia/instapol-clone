<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCartDetailsToInsuranceExtraCoversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurance_extra_covers', function (Blueprint $table) {
            $table->string('plan')->nullable()->after('code');
            $table->integer('cart_day')->nullable()->after('sum_insured');
            $table->decimal('cart_amount', 5, 0)->nullable()->after('cart_day');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insurance_extra_covers', function (Blueprint $table) {
            $table->dropColumn('cart_day');
            $table->dropColumn('cart_amount');
            $table->dropColumn('plan');
        });
    }
}
