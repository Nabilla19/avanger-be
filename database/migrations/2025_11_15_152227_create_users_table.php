<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Identitas dasar
            $table->string('name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('password');

            // Data untuk sistem pinjol
            $table->string('no_hp', 12)->unique();
            $table->string('no_hp2', 12)->unique();
            $table->string('nama_no_hp2');
            $table->string('relasi_no_hp2');
            $table->string('NIK', 16)->unique();
            $table->string('Norek', 20)->unique();
            $table->string('Nama_Ibu');
            $table->string('Pekerjaan');
            $table->string('Gaji', 16);
            $table->string('alamat');

            // Relasi bank
            $table->string('kode_bank', 6);

            // Role
            $table->enum('role', ['admin','owner','customer'])->default('customer');

            // Security
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();

            $table->timestamps();

            // Foreign Key
            $table->foreign('kode_bank')
                ->references('kode_bank')
                ->on('bank')
                ->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
