<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ];

        if (!$request->has('role') || $request->role === 'customer') {
            $rules = array_merge($rules, [
                'no_hp' => 'required|string|max:12|unique:users',
                'no_hp2' => 'required|string|max:12|unique:users',
                'nama_no_hp2' => 'required|string|max:255',
                'relasi_no_hp2' => 'required|string|max:255',
                'NIK' => 'required|string|size:16|unique:users',
                'Norek' => 'required|string|max:20|unique:users',
                'Nama_Ibu' => 'required|string|max:255',
                'Pekerjaan' => 'required|string|max:255',
                'Gaji' => 'required|string|max:16',
                'alamat' => 'required|string',
                'kode_bank' => 'required|string|exists:banks,kode_bank',
            ]);
        }

        $validated = $request->validate($rules);
        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = $validated['role'] ?? 'customer';

        $user = User::create($validated);

        AuditService::log('user_registered', $user, [
            'new' => [
                'email' => $user->email,
                'role'  => $user->role,
            ],
        ], $request);

        $abilities = $this->getAbilitiesByRole($user->role);
        $token = $user->createToken('auth-token', $abilities)->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Registration successful'
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            AuditService::log('login_failed', null, [
                'new' => ['email' => $request->email],
            ], $request);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $abilities = $this->getAbilitiesByRole($user->role);
        $token = $user->createToken('auth-token', $abilities)->plainTextToken;

        AuditService::log('login_success', $user, [], $request);

        return response()->json([
            'user' => $user,
            'token' => $token,
            'message' => 'Login successful'
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        AuditService::log('logout', $user, [], $request);

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    private function getAbilitiesByRole($role)
    {
        switch ($role) {
            case 'owner':
                return ['*'];
            case 'admin':
                return [
                    'peminjaman:read',
                    'peminjaman:approve',
                    'peminjaman:update',
                ];
            case 'customer':
            default:
                return [
                    'peminjaman:read',
                    'peminjaman:create',
                ];
        }
    }
}
