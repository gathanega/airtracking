<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (Auth::user()?->isAdmin()) {
            return redirect()->route('admin.devices');
        }
        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $cred = $request->validate(['email' => 'required|email', 'password' => 'required|string']);

        $key = Str::lower($cred['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.'])
                ->onlyInput('email');
        }

        $ok = Auth::attempt($cred);
        if ($ok && !Auth::user()->isAdmin()) {
            Auth::logout();
            $ok = false;
        }
        if (!$ok) {
            RateLimiter::hit($key, 300);
            return back()->withErrors(['email' => 'Email atau kata sandi salah, atau akun bukan admin.'])->onlyInput('email');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        return redirect()->route('admin.devices');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
