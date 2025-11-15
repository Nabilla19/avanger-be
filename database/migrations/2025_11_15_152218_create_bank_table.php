<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bank', function (Blueprint $table) {
            $table->string('kode_bank', 6)->primary();   // PRIMARY KEY
            $table->string('nama')->unique();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bank');
    }
};
