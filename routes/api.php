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


    // == BANK (AUTHORIZATION) ==

    // 1. Routes yang bisa diakses SEMUA ROLE (customer, admin, owner)
    //    (Karena sudah di dalam 'auth:sanctum', user pasti sudah login)
    Route::get('/bank', [BankController::class, 'index']);
    Route::get('/bank/{kode_bank}', [BankController::class, 'show']);


    // 2. Routes yang HANYA bisa diakses oleh 'admin' dan 'owner'
    //    Kita tambahkan middleware ->middleware('role:admin,owner')
    Route::post('/bank', [BankController::class, 'store']);

    Route::put('/bank/{kode_bank}', [BankController::class, 'update'])
        ->middleware('role:admin,owner');

    Route::delete('/bank/{kode_bank}', [BankController::class, 'destroy'])
        ->middleware('role:admin,owner');

});