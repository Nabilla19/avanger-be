<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PeminjamanController extends Controller
{
    // USER: Lihat riwayat pinjaman sendiri
    public function myLoans()
    {
        $loans = Peminjaman::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($loans);
    }

    // ADMIN/OWNER: Lihat SEMUA pinjaman
    public function index()
    {
        $loans = Peminjaman::with('user')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json($loans);
    }

    // Detail pinjaman (user lihat punya sendiri, admin/owner lihat semua)
    public function show($id)
    {
        $loan = Peminjaman::with('user')->find($id);
        
        if (!$loan) {
            return response()->json(['message' => 'Pinjaman tidak ditemukan'], 404);
        }
        
        // User hanya bisa lihat pinjaman sendiri
        if (Auth::user()->role === 'user' && $loan->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized. Anda hanya bisa melihat pinjaman sendiri.'], 403);
        }

        return response()->json($loan);
    }

    // USER: Ajukan pinjaman baru
    public function store(Request $request)
    {
        $request->validate([
            'nominal' => 'required|numeric|min:100000|max:100000000',
            'rentang' => 'required|in:3 Bulan,6 Bulan,12 Bulan'
        ]);

        // Cek apakah user sudah ada pinjaman pending
        $existingPending = Peminjaman::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->exists();
            
        if ($existingPending) {
            return response()->json([
                'message' => 'Anda masih memiliki pinjaman yang sedang diproses. Tunggu hingga selesai.'
            ], 422);
        }

        $loan = Peminjaman::create([
            'user_id' => Auth::id(),
            'nominal' => $request->nominal,
            'rentang' => $request->rentang,
            'status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Pinjaman berhasil diajukan',
            'data' => $loan
        ], 201);
    }

    // ADMIN: Approve pinjaman
    public function approve($id)
    {
        // Double check - hanya admin yang bisa
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Hanya admin yang bisa approve pinjaman.'
            ], 403);
        }

        $loan = Peminjaman::find($id);
        
        if (!$loan) {
            return response()->json(['message' => 'Pinjaman tidak ditemukan'], 404);
        }

        if ($loan->status !== 'pending') {
            return response()->json([
                'message' => 'Pinjaman sudah diproses sebelumnya.'
            ], 422);
        }

        $loan->update(['status' => 'disetujui']);
        
        return response()->json([
            'message' => 'Pinjaman berhasil disetujui',
            'data' => $loan
        ]);
    }

    // ADMIN: Tolak pinjaman
    public function reject($id)
    {
        // Double check - hanya admin yang bisa
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Hanya admin yang bisa menolak pinjaman.'
            ], 403);
        }

        $loan = Peminjaman::find($id);
        
        if (!$loan) {
            return response()->json(['message' => 'Pinjaman tidak ditemukan'], 404);
        }

        if ($loan->status !== 'pending') {
            return response()->json([
                'message' => 'Pinjaman sudah diproses sebelumnya.'
            ], 422);
        }

        $loan->update(['status' => 'ditolak']);
        
        return response()->json([
            'message' => 'Pinjaman berhasil ditolak',
            'data' => $loan
        ]);
    }

    // ADMIN: Update status pinjaman
    public function updateStatus(Request $request, $id)
    {
        // Double check - hanya admin yang bisa
        if (Auth::user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized. Hanya admin yang bisa update status pinjaman.'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:pending,disetujui,ditolak,selesai'
        ]);

        $loan = Peminjaman::find($id);
        
        if (!$loan) {
            return response()->json(['message' => 'Pinjaman tidak ditemukan'], 404);
        }

        $loan->update(['status' => $request->status]);
        
        return response()->json([
            'message' => 'Status pinjaman berhasil diupdate',
            'data' => $loan
        ]);
    }
}