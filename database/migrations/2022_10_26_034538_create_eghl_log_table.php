<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEghlLogTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('eghl_log', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_type')->default('SALE');
            $table->string('payment_method');
            $table->string('service_id');
            $table->string('payment_id');
            $table->string('order_number');
            $table->string('payment_description');
            $table->decimal('amount');
            $table->string('currency_code');
            $table->string('hash');
            $table->string('txn_status')->nullable();
            $table->string('txn_message')->nullable();
            $table->string('response_hash')->nullable();
            $table->string('issuing_bank')->nullable();
            $table->string('bank_reference')->nullable();
            $table->string('auth_code')->nullable();

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
        Schema::dropIfExists('eghl_log');
    }
}
