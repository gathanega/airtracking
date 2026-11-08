<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthController extends Controller
{
    /** Batas percobaan per akun+IP dan per IP (P0-05). */
    private const MAX_PER_AKUN = 5;

    private const MAX_PER_IP = 20;

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

        $email = Str::lower($cred['email']);
        $key = $email.'|'.$request->ip();
        $ipKey = 'login-ip|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_AKUN)) {
            AuditLog::record('login.diblokir', ['email' => $email, 'cakupan' => 'akun']);

            return back()->withErrors(['email' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.'])
                ->onlyInput('email');
        }

        // Batas per IP mencegah percobaan menyebar ke banyak email dari satu jaringan.
        if (RateLimiter::tooManyAttempts($ipKey, self::MAX_PER_IP)) {
            AuditLog::record('login.diblokir', ['email' => $email, 'cakupan' => 'ip']);

            return back()->withErrors(['email' => 'Terlalu banyak percobaan dari jaringan ini. Coba lagi dalam '.RateLimiter::availableIn($ipKey).' detik.'])
                ->onlyInput('email');
        }

        $ok = Auth::attempt($cred);
        if ($ok && ! Auth::user()->isAdmin()) {
            Auth::logout();
            $ok = false;
        }
        if (! $ok) {
            RateLimiter::hit($key, 300);
            RateLimiter::hit($ipKey, 900);
            AuditLog::record('login.gagal', ['email' => $email]);

            return back()->withErrors(['email' => 'Email atau kata sandi salah, atau akun bukan admin.'])->onlyInput('email');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        AuditLog::record('login.berhasil', ['email' => $email]);

        return redirect()->route('admin.devices');
    }

    public function logout(Request $request): RedirectResponse
    {
        AuditLog::record('logout');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
