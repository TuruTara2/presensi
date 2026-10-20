<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ganti Password - {{ config('app.name') }}</title>
    @include('partials.head')
</head>
<body class="flex min-h-screen items-center justify-center bg-surface p-4">
    <div class="fixed right-4 top-4">@include('partials.theme-toggle')</div>
    <div class="card w-full max-w-md">
        <div class="mb-8">
            <div class="font-heading text-[28px] font-semibold leading-tight text-ink">Presensi <span class="text-primary">QR</span></div>
            <p class="mt-2 text-sm text-body">Buat password baru untuk akunmu.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label class="label" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required class="input">
                @error('email')<p class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
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
