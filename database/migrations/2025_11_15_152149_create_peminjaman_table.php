<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('peminjaman', function (Blueprint $table) {
            $table->id();

            // Relasi ke user
            $table->unsignedBigInteger('user_id');

            // Data pinjaman
            $table->string('nominal', 16);
            $table->enum('rentang', ['3 Bulan','6 Bulan','12 Bulan'])->default('3 Bulan');
            $table->timestamp('Waktu')->useCurrent();
            $table->enum('status', ['pending','disetujui','ditolak','selesai'])
                  ->default('pending');

            $table->timestamps();

            // Foreign key → users
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('peminjaman');
    }
};
