<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminOnly::class,
            'device' => \App\Http\Middleware\EnsureBoundDevice::class,
        ]);

        // Jika aplikasi berada di belakang proxy/Cloudflare/load balancer, isi TRUSTED_PROXIES di .env
        // (contoh: TRUSTED_PROXIES=* atau daftar IP dipisah koma) agar IP asli pengunjung terbaca.
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: $proxies === '*' ? '*' : explode(',', $proxies));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
