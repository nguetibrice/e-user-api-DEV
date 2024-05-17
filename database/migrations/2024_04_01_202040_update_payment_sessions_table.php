<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePaymentSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_sessions', function (Blueprint $table) {
            if (!Schema::hasColumn("payment_sessions", "amount")) {
                $table->integer("amount");
            }
            if (!Schema::hasColumn("payment_sessions", "currency")) {
                $table->string("currency");
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
