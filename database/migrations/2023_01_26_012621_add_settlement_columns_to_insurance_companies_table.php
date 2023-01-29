<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSettlementColumnsToInsuranceCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->string('bank_code')->after('coming_soon');
            $table->string('bank_account_no')->after('bank_code');
            $table->string('email_to')->after('bank_account_no');
            $table->string('email_cc')->after('email_to')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insurance_companies', function (Blueprint $table) {
            $table->dropColumn('bank_code');
            $table->dropColumn('bank_account_no');
            $table->dropColumn('email_to');
            $table->dropColumn('email_cc');
        });
    }
}
