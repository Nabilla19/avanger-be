<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  ...$roles  (Array dari role yang diizinkan, misal: 'admin', 'owner')
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // 1. Cek apakah user sudah login
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // 2. Ambil data user yang sedang login
        $user = $request->user();

        // 3. Loop semua role yang diizinkan untuk route ini
        foreach ($roles as $role) {
            // 4. Jika role user MATCH dengan salah satu role yang diizinkan,
            //    lanjutkan request.
            if ($user->role == $role) {
                return $next($request);
            }
        }

        // 5. Jika user tidak punya role yang diizinkan, tolak.
        return response()->json([
            'message' => 'Forbidden. You do not have the required role to access this resource.'
        ], 403);
    }
}