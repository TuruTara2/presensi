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
        $gagal = 'Verifikasi gagal. Pastikan email dan NIP benar, dan lakukan dari perangkat yang terdaftar di akunmu. Kalau masih gagal (misalnya ganti perangkat atau akun belum pernah dipakai login), hubungi admin.';

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

            return $back($gagal);
        }

        // Harus dari perangkat yang terdaftar di akun (aturan yang sama dengan login karyawan).
        // Akun yang belum pernah login (belum ada perangkat terdaftar) TIDAK bisa direset di sini,
        // kalau tidak, siapa pun yang tahu email + NIP bisa mengambil alih akun sebelum pemiliknya login.
        // Pesannya sengaja sama dengan "email/NIP salah" supaya orang luar tidak bisa menebak NIP yang benar.
        $token = $request->cookie('device_token');
        if ($user->device_hash === null || ! $token || ! hash_equals($user->device_hash, hash('sha256', $token))) {
            RateLimiter::hit($key, 900);

            return $back($gagal);
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
