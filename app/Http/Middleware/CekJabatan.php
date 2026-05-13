<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CekJabatan
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$jabatans): Response
    {
        $user = auth()->user();

        // Jika user tidak login, redirect ke login
        if (!$user) {
            return redirect()->route('login');
        }

        // Cek apakah jabatan user termasuk dalam daftar yang diizinkan
        if (!in_array($user->jabatan, $jabatans)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}