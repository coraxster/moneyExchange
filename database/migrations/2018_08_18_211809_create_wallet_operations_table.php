<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWalletOperationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wallet_operations', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('operation_code')->comment('1 - refill, 2 - transfer');
            $table->unsignedInteger('from_wallet_id')->nullable()->index();
            $table->unsignedInteger('to_wallet_id')->nullable()->index();
            $table->bigInteger('raw_withdraw')->nullable();
            $table->bigInteger('raw_deposit')->nullable();
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
        Schema::dropIfExists('wallet_operations');
    }
}
