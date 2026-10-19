<?php

namespace App\Http\Controllers;

use App\Models\DeviceRequest;
use App\Support\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt(array_merge($cred, ['is_active' => true]), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email / password salah, atau akun nonaktif.'])->onlyInput('email');
        }

        $user = Auth::user();

        // ---- Kunci perangkat (hanya karyawan; admin dikecualikan)
        if (! $user->isAdmin()) {
            $token = $request->cookie('device_token') ?: Str::random(48);
            Cookie::queue('device_token', $token, 60 * 24 * 730); // ~2 tahun
            $hash = hash('sha256', $token);

            if ($user->device_hash === null) {
                // Login pertama: perangkat ini otomatis menjadi perangkat terdaftar
                $user->forceFill([
                    'device_hash' => $hash,
                    'device_name' => Device::label($request->userAgent()),
                    'device_bound_at' => now(),
                ])->save();
            } elseif (! hash_equals($user->device_hash, $hash)) {
                // Perangkat lain: tolak, catat permintaan untuk disetujui admin
                DeviceRequest::updateOrCreate(
                    ['user_id' => $user->id, 'device_hash' => $hash, 'status' => 'pending'],
                    ['user_agent' => substr((string) $request->userAgent(), 0, 255), 'ip' => $request->ip()]
                );
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun ini terikat ke perangkat lain. Permintaan memakai perangkat baru sudah dikirim ke admin untuk disetujui.',
                ])->onlyInput('email');
            }
        }

        $request->session()->regenerate();

        // ---- Hanya satu sesi aktif per karyawan
        if (! $user->isAdmin()) {
            DB::table('sessions')->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())->delete();
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
