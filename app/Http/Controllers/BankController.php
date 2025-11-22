<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;

class BankController extends Controller
{
    // GET /api/bank
    public function index()
    {
        $banks = Bank::all();
        return response()->json([
            'message' => 'Data bank berhasil diambil',
            'data' => $banks
        ]);
    }

    // GET /api/bank/{kode_bank}
    public function show($kode_bank)
    {
        $bank = Bank::find($kode_bank);

        if (!$bank) {
            return response()->json(['message' => 'Bank tidak ditemukan'], 404);
        }

        return response()->json([
            'message' => 'Data bank berhasil diambil',
            'data' => $bank
        ]);
    }

    // POST /api/bank
    public function store(Request $request)
    {
        $request->validate([
            'kode_bank' => 'required|string|max:10|unique:banks,kode_bank',
            'nama_bank' => 'required|string|max:100|unique:banks,nama_bank',
            'alamat' => 'required|string',
            'kota' => 'required|string|max:50',
            'provinsi' => 'required|string|max:50'
        ]);

        $bank = Bank::create([
            'kode_bank' => $request->kode_bank,
            'nama_bank' => $request->nama_bank,
            'alamat' => $request->alamat,
            'kota' => $request->kota,
            'provinsi' => $request->provinsi
        ]);

        return response()->json([
            'message' => 'Bank berhasil ditambahkan',
            'data' => $bank
        ], 201);
    }

    // PUT /api/bank/{kode_bank}
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

        $bank->update([
            'nama_bank' => $request->nama_bank,
            'alamat' => $request->alamat,
            'kota' => $request->kota,
            'provinsi' => $request->provinsi
        ]);

        return response()->json([
            'message' => 'Bank berhasil diupdate',
            'data' => $bank
        ]);
    }

    // DELETE /api/bank/{kode_bank}
    public function destroy($kode_bank)
    {
        $bank = Bank::find($kode_bank);

        if (!$bank) {
            return response()->json(['message' => 'Bank tidak ditemukan'], 404);
        }

        // Cek apakah bank masih digunakan oleh user
        if ($bank->users()->exists()) {
            return response()->json([
                'message' => 'Tidak bisa menghapus bank. Masih ada user yang menggunakan bank ini.'
            ], 422);
        }

        $bank->delete();

        return response()->json([
            'message' => 'Bank berhasil dihapus'
        ]);
    }
}