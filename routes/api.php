<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankController;
use App\Http\Controllers\PeminjamanController;

/*
|--------------------------------------------------------------------------
| Public Routes (Tidak perlu login)
|--------------------------------------------------------------------------
*/
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:3,1');


/*
|--------------------------------------------------------------------------
| Protected Routes (WAJIB Login / Punya Token)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'check.token.expiry'])->group(function () {

    // == AUTHENTICATION ==
    Route::get('/user', function(Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);


    // == BANK ROUTES ==
    Route::prefix('bank')->group(function () {
        // Read operations - butuh ability bank:read
        Route::get('/', [BankController::class, 'index'])
            ->middleware(['abilities:bank:read']);
        Route::get('/{kode_bank}', [BankController::class, 'show'])
            ->middleware(['abilities:bank:read']);

        // Write operations - butuh role admin/owner + abilities
        Route::post('/', [BankController::class, 'store'])
            ->middleware(['role:owner', 'abilities:bank:create']);
        Route::put('/{kode_bank}', [BankController::class, 'update'])
            ->middleware(['role:owner', 'abilities:bank:update']);
        Route::delete('/{kode_bank}', [BankController::class, 'destroy'])
            ->middleware(['role:owner', 'abilities:bank:delete']);
    });


    // == PEMINJAMAN ROUTES ==
    Route::prefix('peminjaman')->group(function () {
        // User: Lihat pinjaman sendiri & ajukan pinjaman
        Route::get('/my', [PeminjamanController::class, 'myLoans'])
            ->middleware(['abilities:peminjaman:read']);
        Route::get('/{id}', [PeminjamanController::class, 'show'])
            ->middleware(['abilities:peminjaman:read']);
        Route::post('/', [PeminjamanController::class, 'store'])
            ->middleware(['abilities:peminjaman:create']);

        // Admin/Owner: Management semua pinjaman
        Route::get('/', [PeminjamanController::class, 'index'])
            ->middleware(['role:admin,owner', 'abilities:peminjaman:read']);
        Route::put('/{id}/approve', [PeminjamanController::class, 'approve'])
            ->middleware(['role:admin', 'abilities:peminjaman:approve']);
        Route::put('/{id}/reject', [PeminjamanController::class, 'reject'])
            ->middleware(['role:admin', 'abilities:peminjaman:approve']);
        Route::put('/{id}/status', [PeminjamanController::class, 'updateStatus'])
            ->middleware(['role:admin', 'abilities:peminjaman:update']);
    });

});