<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AllForexSymbols extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('AllForexSymbols', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20);
            $table->string('currency', 5);
            $table->string('categoryName', 5);
            $table->string('groupName', 100);
            $table->double('open', 9, 4)->nullable();
            $table->double('close', 9, 4)->nullable();
            $table->double('tr', 9, 4)->nullable();
            $table->double('atr', 9, 4)->nullable();
            $table->double('bid', 9, 4);
            $table->double('high', 9, 4);
            $table->double('low', 9, 4);
            $table->timestamp('time');
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
        Schema::drop('AllForexSymbols');
    }
}
