<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - {{ config('app.name') }}</title>
    @include('partials.head')
</head>
<body class="flex min-h-screen items-center justify-center bg-surface p-4">
    <div class="fixed right-4 top-4">@include('partials.theme-toggle')</div>
    <div class="card w-full max-w-md">
        <div class="mb-8">
            <div class="font-heading text-[28px] font-semibold leading-tight text-ink">Presensi <span class="text-primary">QR</span></div>
            <p class="mt-2 text-sm text-body">Masuk dengan akun karyawan atau admin.</p>
        </div>

        @if (session('status'))
            <div class="mb-6 rounded-xl border border-success/30 bg-success/10 px-4 py-4 text-sm text-success-text">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-6">
            @csrf
            <div>
                <label class="label" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="input">
                @error('email')<p class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password">Password</label>
                @include('partials.password-input', ['name' => 'password', 'required' => true, 'autocomplete' => 'current-password'])
            </div>
            <div class="flex items-center justify-between">
                <label class="flex items-center gap-2 text-sm text-body">
                    <input type="checkbox" name="remember"> Ingat saya
                </label>
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-primary hover:underline">Lupa password?</a>
            </div>
            <button class="btn btn-lg btn-primary w-full">Masuk</button>
        </form>
        <a href="{{ route('scan') }}" class="mt-6 block text-center text-sm font-semibold text-primary hover:underline">Kembali ke halaman scan QR</a>
    </div>
</body>
</html>
