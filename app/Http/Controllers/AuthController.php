<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    // REGISTER
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'username' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',

            'no_hp' => 'required|unique:users',
            'no_hp2' => 'required|unique:users',
            'nama_no_hp2' => 'required',
            'relasi_no_hp2' => 'required',
            'NIK' => 'required|unique:users',
            'Norek' => 'required|unique:users',
            'Nama_Ibu' => 'required',
            'Pekerjaan' => 'required',
            'Gaji' => 'required',
            'alamat' => 'required',

            'kode_bank' => 'required|exists:bank,kode_bank',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),

            'no_hp' => $request->no_hp,
            'no_hp2' => $request->no_hp2,
            'nama_no_hp2' => $request->nama_no_hp2,
            'relasi_no_hp2' => $request->relasi_no_hp2,
            'NIK' => $request->NIK,
            'Norek' => $request->Norek,
            'Nama_Ibu' => $request->Nama_Ibu,
            'Pekerjaan' => $request->Pekerjaan,
            'Gaji' => $request->Gaji,
            'alamat' => $request->alamat,
            'kode_bank' => $request->kode_bank,
        ]);

        $token = $user->createToken('authToken', ['user:read', 'user:write'])->plainTextToken;

        return response()->json([
            'message' => 'Register berhasil',
            'token' => $token,
            'user' => $user,
        ], 201);
    }

    // LOGIN
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        sleep(1); // anti brute force
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Email atau password salah'], 401);
        }

        // Cek Role untuk menentukan kekuatan Token
        if ($user->role == 'admin') {
            // Admin punya kekuatan penuh: create, update, delete
            $abilities = ['bank:create', 'bank:update', 'bank:delete', 'bank:read'];
        } else {
            // Customer cuma bisa baca (read)
            $abilities = ['bank:read'];
        }

        // Buat token dengan abilities tersebut
        $token = $user->createToken('authToken', $abilities)->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $user
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }
}
