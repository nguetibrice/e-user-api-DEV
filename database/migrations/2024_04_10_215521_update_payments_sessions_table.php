<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePaymentsSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_sessions', function (Blueprint $table) {
            $table->integer("order_id")->nullable()->change();
            $table->integer("order_id")->dropUnique("payment_sessions_order_id_unique")->change();
            $table->text("order_type")->change();
            $table->string("price_id")->nullable()->change();
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
