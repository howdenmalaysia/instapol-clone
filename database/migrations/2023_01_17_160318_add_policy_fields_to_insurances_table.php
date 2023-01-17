<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPolicyFieldsToInsurancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->string('policy_number')->nullable()->after('updated_by');
            $table->string('cover_note_number')->nullable()->after('updated_by');
            $table->date('cover_note_date')->nullable()->after('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('insurances', function (Blueprint $table) {
            $table->dropColumn('policy_number');
            $table->dropColumn('cover_note_number');
            $table->dropColumn('cover_note_date');
        });
    }
}
