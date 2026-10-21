<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Lupa password khusus karyawan, langsung buat password baru (tanpa email).
 * Verifikasi: email + NIP harus cocok, dan permintaan harus dari perangkat yang terdaftar di akun.
 */
class PasswordResetController extends Controller
{
    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'nip' => ['required', 'string', 'max:50'],
            'password' => ['required', 'min:6', 'confirmed'],
        ], [
            'password.min' => 'Password baru minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama dengan password baru.',
        ]);

        // Batasi tebak-tebakan NIP: maks 5 percobaan gagal per email+IP selama 15 menit
        $key = 'reset-password:'.Str::lower($data['email']).'|'.$request->ip();
        $back = fn (string $msg) => back()->withInput($request->only('email', 'nip'))->withErrors(['email' => $msg]);

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $menit = (int) ceil(RateLimiter::availableIn($key) / 60);

            return $back("Terlalu banyak percobaan. Coba lagi dalam {$menit} menit.");
        }

        // Hanya akun karyawan aktif. Admin tidak bisa reset sendiri lewat halaman ini.
        $user = User::where('email', $data['email'])
            ->where('role', 'karyawan')
            ->where('is_active', true)
            ->whereNotNull('nip')
            ->where('nip', trim($data['nip']))
            ->first();

        if (! $user) {
            RateLimiter::hit($key, 900);

            return $back('Email dan NIP tidak cocok, atau akun tidak bisa direset di sini. Hubungi admin.');
        }

        // Harus dari perangkat yang terdaftar di akun (aturan yang sama dengan login karyawan)
        if ($user->device_hash !== null) {
            $token = $request->cookie('device_token');
            if (! $token || ! hash_equals($user->device_hash, hash('sha256', $token))) {
                RateLimiter::hit($key, 900);

                return $back('Reset password hanya bisa dilakukan dari perangkat yang terdaftar di akunmu. Kalau kamu ganti perangkat, hubungi admin.');
            }
        }

        $user->forceFill([
            'password' => $data['password'], // di-hash otomatis oleh cast 'hashed'
            'remember_token' => Str::random(60),
        ])->save();

        // Sesi lama dikeluarkan
        DB::table('sessions')->where('user_id', $user->id)->delete();
        RateLimiter::clear($key);

        return redirect()->route('login')->with('status', 'Password berhasil diganti. Silakan masuk dengan password baru.');
    }
}
