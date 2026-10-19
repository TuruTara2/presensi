<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Karyawan hanya boleh memakai akunnya dari perangkat yang terdaftar. */
class EnsureBoundDevice
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->isAdmin() && $user->device_hash) {
            $token = $request->cookie('device_token');
            if (! $token || ! hash_equals($user->device_hash, hash('sha256', $token))) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Sesi dihentikan: perangkat tidak dikenali atau sudah direset admin. Silakan login lagi.',
                ]);
            }
        }

        return $next($request);
    }
}
