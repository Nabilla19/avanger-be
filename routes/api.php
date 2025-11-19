<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankController;

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
|
| Semua route di dalam grup ini dilindungi oleh 'auth:sanctum'.
| Kita tambahkan middleware 'role' untuk otorisasi.
|
*/
Route::middleware(['auth:sanctum', 'check.token.expiry'])->group(function () {

    // == AUTHENTICATION ==
    // Semua user yang login bisa mengakses ini
    Route::get('/user', function(Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);


    // --- BANK ROUTES ---

    // 1. GET: Token harus punya kemampuan 'bank:read'
    Route::get('/bank', [BankController::class, 'index'])
        ->middleware(['abilities:bank:read']); 
        
    Route::get('/bank/{kode_bank}', [BankController::class, 'show'])
        ->middleware(['abilities:bank:read']);


    // 2. POST: Harus Admin DAN Token harus punya 'bank:create'
    Route::post('/bank', [BankController::class, 'store'])
        ->middleware(['role:admin,owner', 'abilities:bank:create']);

    // 3. PUT: Harus Admin DAN Token harus punya 'bank:update'
    Route::put('/bank/{kode_bank}', [BankController::class, 'update'])
        ->middleware(['role:admin,owner', 'abilities:bank:update']);

    // 4. DELETE: Harus Admin DAN Token harus punya 'bank:delete'
    Route::delete('/bank/{kode_bank}', [BankController::class, 'destroy'])
        ->middleware(['role:admin,owner', 'abilities:bank:delete']);

});