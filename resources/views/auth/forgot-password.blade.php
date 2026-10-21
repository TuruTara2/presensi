<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lupa Password - {{ config('app.name') }}</title>
    @include('partials.head')
</head>
<body class="flex min-h-screen items-center justify-center bg-surface p-4">
    <div class="fixed right-4 top-4">@include('partials.theme-toggle')</div>
    <div class="card w-full max-w-md">
        <div class="mb-8">
            <div class="font-heading text-[28px] font-semibold leading-tight text-ink">Presensi <span class="text-primary">QR</span></div>
            <p class="mt-2 text-sm text-body">Lupa password? Verifikasi dengan email dan NIP-mu, lalu buat password baru. Harus dilakukan dari perangkat yang terdaftar.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
            @csrf
            <div>
                <label class="label" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="input">
                @error('email')<p class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="nip">NIP</label>
                <input id="nip" type="text" name="nip" value="{{ old('nip') }}" required class="input" autocomplete="off">
                @error('nip')<p class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password">Password baru</label>
                @include('partials.password-input', ['name' => 'password', 'required' => true, 'autocomplete' => 'new-password'])
                @error('password')<p class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label" for="password_confirmation">Ulangi password baru</label>
                @include('partials.password-input', ['name' => 'password_confirmation', 'required' => true, 'autocomplete' => 'new-password'])
            </div>
            <button class="btn btn-lg btn-primary w-full">Ganti Password</button>
        </form>
        <a href="{{ route('login') }}" class="mt-6 block text-center text-sm font-semibold text-primary hover:underline">Kembali ke halaman masuk</a>
    </div>
</body>
</html>
