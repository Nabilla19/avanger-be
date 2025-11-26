<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_peminjaman', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('peminjaman_id');
            $table->unsignedBigInteger('user_id');
            $table->string('nominal', 16);
            $table->string('metode')->nullable();      // contoh: transfer, cash
            $table->string('keterangan')->nullable(); // optional catatan
            $table->timestamp('tanggal_bayar')->useCurrent();
            $table->timestamps();

            $table->foreign('peminjaman_id')
                ->references('id')->on('peminjaman')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_peminjaman');
    }
};
