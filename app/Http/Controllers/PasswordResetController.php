<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/** Lupa password: kirim link reset lewat email, lalu ganti password baru. */
class PasswordResetController extends Controller
{
    public function showForgot()
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        try {
            // Hanya akun aktif yang dikirimi link. Pesan balasan selalu sama supaya email terdaftar tidak bisa ditebak.
            Password::sendResetLink(['email' => $request->email, 'is_active' => true]);
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['email' => 'Email gagal dikirim. Coba lagi nanti atau hubungi admin.']);
        }

        return back()->with('status', 'Kalau email itu terdaftar, link reset password sudah dikirim. Cek inbox (dan folder spam).');
    }

    public function showReset(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token') + ['is_active' => true],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password, // di-hash otomatis oleh cast 'hashed'
                    'remember_token' => Str::random(60),
                ])->save();

                // Semua sesi lama (mis. di perangkat yang hilang) dikeluarkan
                DB::table('sessions')->where('user_id', $user->id)->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Password berhasil diganti. Silakan masuk dengan password baru.');
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => 'Link reset tidak valid atau sudah kedaluwarsa. Silakan minta link baru.']);
    }
}
