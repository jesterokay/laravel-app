<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrenciesTable extends Migration
{
    public function up()
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 3)->unique();
            $table->string('name');
            $table->string('symbol');
            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists('currencies');
    }
}