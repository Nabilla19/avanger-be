<?php

namespace App\Http\Controllers;

use App\Models\Peminjaman;
use App\Models\PembayaranPeminjaman;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PeminjamanController extends Controller
{
    public function myLoans()
    {
        $loans = Peminjaman::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $loans]);
    }

    public function index()
    {
        $loans = Peminjaman::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $loans]);
    }

    public function show($id)
    {
        $loan = Peminjaman::with('user')->find($id);

        if (!$loan) {
            return response()->json(['message' => 'Pinjaman tidak ditemukan'], 404);
        }

        if (Auth::user()->role === 'customer' && $loan->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized. Anda hanya bisa melihat pinjaman sendiri.'
            ], 403);
        }

        return response()->json(['data' => $loan]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nominal' => 'required|string|max:16',
            'rentang' => 'required|in:3 Bulan,6 Bulan,12 Bulan'
        ]);

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
            'Waktu' => now(),
            'status' => 'pending'
        ]);

        AuditService::log('loan_created', $loan, [
            'new' => [
                'nominal' => $loan->nominal,
                'rentang' => $loan->rentang,
                'status'  => $loan->status,
            ],
        ], $request);

        return response()->json([
            'message' => 'Pinjaman berhasil diajukan',
            'data' => $loan
        ], 201);
    }

    public function approve($id)
    {
        if (!in_array(Auth::user()->role, ['admin', 'owner'])) {
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

        $oldStatus = $loan->status;
        $loan->update(['status' => 'disetujui']);

        AuditService::log('loan_approved', $loan, [
            'old' => ['status' => $oldStatus],
            'new' => ['status' => $loan->status],
        ]);

        return response()->json([
            'message' => 'Pinjaman berhasil disetujui',
            'data' => $loan
        ]);
    }

    public function reject($id)
    {
        if (!in_array(Auth::user()->role, ['admin', 'owner'])) {
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

        $oldStatus = $loan->status;
        $loan->update(['status' => 'ditolak']);

        AuditService::log('loan_rejected', $loan, [
            'old' => ['status' => $oldStatus],
            'new' => ['status' => $loan->status],
        ]);

        return response()->json([
            'message' => 'Pinjaman berhasil ditolak',
            'data' => $loan
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        if (!in_array(Auth::user()->role, ['admin', 'owner'])) {
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

        $oldStatus = $loan->status;
        $loan->update(['status' => $request->status]);

        AuditService::log('loan_status_updated', $loan, [
            'old' => ['status' => $oldStatus],
            'new' => ['status' => $loan->status],
        ], $request);

        return response()->json([
            'message' => 'Status pinjaman berhasil diupdate',
            'data' => $loan
        ]);
    }

    public function payments($id)
    {
        $loan = Peminjaman::with('user')->find($id);

        if (!$loan) {
            return response()->json(['message' => 'Pinjaman tidak ditemukan'], 404);
        }

        $user = Auth::user();
        if ($user->role === 'customer' && $loan->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payments = PembayaranPeminjaman::where('peminjaman_id', $loan->id)
            ->orderBy('tanggal_bayar', 'desc')
            ->get();

        $totalPaid = $payments->sum(function ($p) {
            return (int) $p->nominal;
        });

        $totalLoan = (int) $loan->nominal;
        $sisa = max($totalLoan - $totalPaid, 0);

        return response()->json([
            'loan' => $loan,
            'payments' => $payments,
            'summary' => [
                'total_pinjam' => $totalLoan,
                'total_bayar'  => $totalPaid,
                'sisa'         => $sisa,
            ],
        ]);
    }

    public function pay(Request $request, $id)
    {
        $request->validate([
            'nominal' => 'required|numeric|min:1000',
            'metode' => 'nullable|string|max:50',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $user = Auth::user();

        $loan = Peminjaman::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$loan) {
            return response()->json(['message' => 'Pinjaman tidak ditemukan'], 404);
        }

        if ($loan->status === 'selesai') {
            return response()->json(['message' => 'Pinjaman sudah lunas'], 422);
        }

        $totalLoan = (int) $loan->nominal;

        $totalPaid = PembayaranPeminjaman::where('peminjaman_id', $loan->id)
            ->sum(DB::raw('CAST(nominal as SIGNED)'));

        $sisa = max($totalLoan - $totalPaid, 0);

        $bayar = (int) $request->nominal;

        if ($bayar <= 0) {
            return response()->json(['message' => 'Nominal pembayaran tidak valid'], 422);
        }

        if ($bayar > $sisa) {
            return response()->json([
                'message' => 'Nominal pembayaran melebihi sisa hutang',
                'sisa' => $sisa,
            ], 422);
        }

        $payment = PembayaranPeminjaman::create([
            'peminjaman_id' => $loan->id,
            'user_id'       => $user->id,
            'nominal'       => (string) $bayar,
            'metode'        => $request->metode,
            'keterangan'    => $request->keterangan,
            'tanggal_bayar' => now(),
        ]);

        $totalPaidAfter = $totalPaid + $bayar;
        $sisaAfter = max($totalLoan - $totalPaidAfter, 0);

        if ($sisaAfter <= 0) {
            $loan->status = 'selesai';
            $loan->save();
        }

        AuditService::log('loan_payment_created', $loan, [
            'new' => [
                'payment_nominal' => $payment->nominal,
                'total_bayar'     => $totalPaidAfter,
                'sisa'            => $sisaAfter,
            ],
        ], $request);

        if ($sisaAfter <= 0) {
            AuditService::log('loan_fully_paid', $loan, [
                'old' => ['status' => $loan->getOriginal('status')],
                'new' => ['status' => $loan->status],
            ], $request);
        }

        return response()->json([
            'message' => 'Pembayaran berhasil',
            'loan' => $loan->refresh(),
            'payment' => $payment,
            'summary' => [
                'total_pinjam' => $totalLoan,
                'total_bayar'  => $totalPaidAfter,
                'sisa'         => $sisaAfter,
            ],
        ], 201);
    }
}
