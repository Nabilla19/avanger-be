<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\PeminjamanController;
use App\Http\Controllers\UserController;

/*
|--------------------------------------------------------------------------
| Public Routes (Tidak perlu login)
|--------------------------------------------------------------------------
*/

// Auth Public
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:3,1');

// Bank Public (Agar form Register bisa ambil list bank)
Route::prefix('bank')->group(function () {
    Route::get('/', [BankController::class, 'index']);
    Route::get('/{kode_bank}', [BankController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (WAJIB Login / Punya Token)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {

    // == AUTHENTICATION ==
    Route::get('/user', function(Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // == BANK ROUTES (Write Only) ==
    Route::prefix('bank')->group(function () {
        Route::post('/', [BankController::class, 'store'])
            ->middleware(['role:owner']);
        Route::put('/{kode_bank}', [BankController::class, 'update'])
            ->middleware(['role:owner']);
        Route::delete('/{kode_bank}', [BankController::class, 'destroy'])
            ->middleware(['role:owner']);
    });

    // == USER MANAGEMENT ROUTES (OWNER ONLY) ==
    Route::prefix('users')->middleware(['role:owner'])->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);
    });

    // == AUDIT LOGS ROUTES (OWNER ONLY) ==
    Route::prefix('audit-logs')->middleware(['role:owner'])->group(function () {
        Route::get('/', [AuditLogController::class, 'index']);
        Route::get('/entity/{entityType}/{entityId}', [AuditLogController::class, 'getEntityLogs']);
    });

    // == PEMINJAMAN ROUTES ==
    Route::prefix('peminjaman')->group(function () {
        // Customer routes
        Route::get('/my', [PeminjamanController::class, 'myLoans']);
        Route::get('/{id}', [PeminjamanController::class, 'show']);
        Route::post('/', [PeminjamanController::class, 'store']);

        // Admin routes
        Route::get('/', [PeminjamanController::class, 'index'])
            ->middleware(['role:admin,owner']);
        Route::put('/{id}/approve', [PeminjamanController::class, 'approve'])
            ->middleware(['role:admin']);
        Route::put('/{id}/reject', [PeminjamanController::class, 'reject'])
            ->middleware(['role:admin']);
        Route::put('/{id}/status', [PeminjamanController::class, 'updateStatus'])
            ->middleware(['role:admin']);

        // Payment routes
        Route::post('/{id}/bayar', [PeminjamanController::class, 'pay'])
            ->middleware(['role:customer']);
        Route::get('/{id}/pembayaran', [PeminjamanController::class, 'payments'])
            ->middleware(['role:customer,admin,owner']);
    });

});
