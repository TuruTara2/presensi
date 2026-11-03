<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Melarang browser menyimpan halaman di cache.
 * Efeknya: tombol Back tidak menampilkan halaman lama (isian login, atau dashboard setelah logout),
 * browser akan meminta ulang ke server.
 */
class NoCacheHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
