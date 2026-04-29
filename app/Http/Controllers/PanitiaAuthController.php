<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Panitia;
use Illuminate\Support\Facades\Auth;

class PanitiaAuthController extends Controller
{
    public function showLogin() {
        return view('auth.login');
    }

    public function login(Request $request) {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::guard('panitia')->attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended('dashboard');
        } 

        return back()->withErrors([
            'username' => 'Username atau Password salah.'
        ]);
    }

    public function logout(Request $request) {
        Auth::guard('panitia')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
