<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;

    protected $table = 'banks';
    protected $primaryKey = 'kode_bank';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'kode_bank', 
        'nama_bank', 
        'alamat', 
        'kota', 
        'provinsi'
    ];

    // Relationship ke User
    public function users()
    {
        return $this->hasMany(User::class, 'kode_bank', 'kode_bank');
    }
}