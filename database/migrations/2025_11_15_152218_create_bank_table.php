<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->string('kode_bank', 10)->primary();   // PRIMARY KEY
            $table->string('nama_bank', 100)->unique();
            $table->text('alamat');
            $table->string('kota', 50);
            $table->string('provinsi', 50);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('banks');
    }
};