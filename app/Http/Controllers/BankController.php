<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    // GET /api/bank (PUBLIC)
    public function index()
    {
        $banks = Bank::all();
        return response()->json($banks);
    }

    // GET /api/bank/{kode_bank} (PUBLIC)
    public function show($kode_bank)
    {
        $bank = Bank::find($kode_bank);

        if (!$bank) {
            return response()->json(['message' => 'Bank tidak ditemukan'], 404);
        }

        return response()->json($bank);
    }

    // POST /api/bank (PROTECTED - OWNER)
    public function store(Request $request)
    {
        $request->validate([
            'kode_bank' => 'required|string|max:10|unique:banks,kode_bank',
            'nama_bank' => 'required|string|max:100|unique:banks,nama_bank',
            'alamat' => 'required|string',
            'kota' => 'required|string|max:50',
            'provinsi' => 'required|string|max:50'
        ]);

        $bank = Bank::create($request->all());

        return response()->json([
            'message' => 'Bank berhasil ditambahkan',
            'data' => $bank
        ], 201);
    }

    // PUT /api/bank/{kode_bank} (PROTECTED - OWNER)
    public function update(Request $request, $kode_bank)
    {
        $bank = Bank::find($kode_bank);

        if (!$bank) {
            return response()->json(['message' => 'Bank tidak ditemukan'], 404);
        }

        $request->validate([
            'nama_bank' => 'required|string|max:100|unique:banks,nama_bank,' . $kode_bank . ',kode_bank',
            'alamat' => 'required|string',
            'kota' => 'required|string|max:50',
            'provinsi' => 'required|string|max:50'
        ]);

        $bank->update($request->all());

        return response()->json([
            'message' => 'Bank berhasil diupdate',
            'data' => $bank
        ]);
    }

    // DELETE /api/bank/{kode_bank} (PROTECTED - OWNER)
    public function destroy($kode_bank)
    {
        $bank = Bank::find($kode_bank);

        if (!$bank) {
            return response()->json(['message' => 'Bank tidak ditemukan'], 404);
        }

        // Cek relasi user sebelum hapus
        try {
            if (method_exists($bank, 'users') && $bank->users()->exists()) {
                return response()->json([
                    'message' => 'Tidak bisa menghapus bank. Masih ada user yang menggunakan bank ini.'
                ], 422);
            }
        } catch (\Exception $e) {
            // Abaikan jika relasi belum siap
        }

        $bank->delete();

        return response()->json([
            'message' => 'Bank berhasil dihapus'
        ]);
    }
}
