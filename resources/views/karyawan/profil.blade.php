@extends('layouts.app')
@section('title', 'Profil Saya')
@section('content')
<form method="POST" action="{{ route('karyawan.profil.update') }}" class="card max-w-xl space-y-4">
    @csrf @method('PUT')
    <div class="grid grid-cols-2 gap-4 rounded-xl bg-slate-50 p-4 text-sm">
        <div><span class="text-slate-500">NIP</span><div class="font-medium">{{ $user->nip ?? '-' }}</div></div>
        <div><span class="text-slate-500">Divisi</span><div class="font-medium">{{ $user->division->name ?? '-' }}</div></div>
    </div>
    <div><label class="label">Nama</label><input name="name" value="{{ old('name', $user->name) }}" class="input" required></div>
    <div><label class="label">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required></div>
    <div><label class="label">No. HP</label><input name="phone" value="{{ old('phone', $user->phone) }}" class="input"></div>
    <hr>
    <p class="text-sm text-slate-500">Isi hanya jika ingin ganti password.</p>
    <div><label class="label" for="current_password">Password Saat Ini</label>
        @include('partials.password-input', ['name' => 'current_password', 'autocomplete' => 'current-password'])</div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label" for="password">Password Baru</label>
            @include('partials.password-input', ['name' => 'password', 'autocomplete' => 'new-password'])</div>
        <div><label class="label" for="password_confirmation">Konfirmasi</label>
            @include('partials.password-input', ['name' => 'password_confirmation', 'autocomplete' => 'new-password'])</div>
    </div>
    <button class="btn-primary">Simpan Perubahan</button>
</form>
@endsection
