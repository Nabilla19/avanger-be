<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PembayaranPeminjaman extends Model
{
    protected $table = 'pembayaran_peminjaman';

    protected $fillable = [
        'peminjaman_id',
        'user_id',
        'nominal',
        'metode',
        'keterangan',
        'tanggal_bayar',
    ];

    protected $casts = [
        'tanggal_bayar' => 'datetime',
    ];

    public function peminjaman()
    {
        return $this->belongsTo(Peminjaman::class, 'peminjaman_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
