<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentMethodsTable extends Migration
{
    public function up()
    {
        Schema::create('payment_methods', function (Blueprint $table) {

            $table->bigIncrements('id');
            $table->string('country', 2)->default('CM');
            $table->string('name', 191)->nullable();
            $table->string('image', 191)->nullable();
            $table->decimal('min_limit',18, 8)->default('0.00000000');
            $table->decimal('max_limit',18, 8)->default('0.00000000');
            $table->text('description')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_methods');
    }
}
