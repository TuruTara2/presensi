<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Presensi') - {{ config('app.name') }}</title>
    @include('partials.head')
    @stack('head')
</head>
<body class="min-h-screen bg-page">
@php
    $isAdmin = auth()->user()->isAdmin();
    $menu = $isAdmin ? [
        ['admin.dashboard', 'Dashboard', 'admin.dashboard'],
        ['admin.karyawan.index', 'Karyawan', 'admin.karyawan.*'],
        ['admin.divisi', 'Divisi', 'admin.divisi'],
        ['admin.shift', 'Jam Kerja', 'admin.shift'],
        ['admin.izin', 'Persetujuan', 'admin.izin'],
        ['admin.jaringan', 'Jaringan', 'admin.jaringan'],
        ['admin.perangkat', 'Perangkat', 'admin.perangkat'],
        ['admin.laporan', 'Laporan', 'admin.laporan'],
    ] : [
        ['karyawan.dashboard', 'Presensi', 'karyawan.dashboard'],
        ['karyawan.riwayat', 'Riwayat', 'karyawan.riwayat'],
        ['karyawan.izin', 'Izin / Sakit / Cuti', 'karyawan.izin'],
        ['karyawan.profil', 'Profil', 'karyawan.profil'],
    ];
@endphp

<header class="border-b border-line bg-page">
    <div class="mx-auto flex max-w-page items-center justify-between px-4 py-4 md:px-6">
        <div>
            <div class="font-heading text-[22px] font-semibold leading-tight text-ink">Presensi <span class="text-primary">QR</span></div>
            <div class="text-sm text-slate-500">{{ $isAdmin ? 'Panel Admin' : 'Portal Karyawan' }}</div>
        </div>
        <div class="flex items-center gap-4">
            <span class="hidden text-sm text-body sm:inline">{{ auth()->user()->name }}</span>
            @include('partials.theme-toggle')
            <form method="POST" action="{{ route('logout') }}">@csrf
                <button class="btn btn-light">Keluar</button>
            </form>
        </div>
    </div>
    <nav class="mx-auto max-w-page overflow-x-auto px-4 md:px-6">
        <div class="flex gap-6">
            @foreach ($menu as [$route, $label, $pattern])
                <a href="{{ route($route) }}" class="nav-link {{ request()->routeIs($pattern) ? 'nav-link-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </nav>
</header>

<main class="mx-auto max-w-page px-4 py-8 md:px-6">
    <h1 class="h3 mb-6">@yield('title')</h1>

    @if (session('success'))
        <div class="mb-6 rounded-xl border border-success/30 bg-success/10 px-4 py-4 text-sm text-success-text">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-xl bg-error-container px-4 py-4 text-sm text-error-on">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-xl bg-error-container px-4 py-4 text-sm text-error-on">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @yield('content')
</main>
@stack('scripts')
</body>
</html>
