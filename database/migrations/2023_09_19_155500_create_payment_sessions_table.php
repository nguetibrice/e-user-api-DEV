<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid("reference")->unique();
            $table->integer("order_id")->unique();
            $table->enum("order_type",["SUBSCRIPTION"]);
            $table->string("price_id");
            $table->enum("payment_method",["ORANGE_MONEY","MTN_MOMO","STRIPE_MOBILE","STRIPE_CARD"]);
            $table->string("transaction_id")->default(null)->nullable();
            $table->text("payment_url")->default(null)->nullable();
            $table->text("payment_token")->default(null)->nullable();
            $table->text("notification_token")->default(null)->nullable();
            $table->integer("status")->default(0);
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
        Schema::dropIfExists('payment_sessions');
    }
}
