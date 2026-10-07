@extends('layouts.app')
@section('title', $user->exists ? 'Edit Karyawan' : 'Tambah Karyawan')
@section('content')
<form method="POST" action="{{ $user->exists ? route('admin.karyawan.update', $user) : route('admin.karyawan.store') }}" class="card max-w-2xl space-y-4">
    @csrf
    @if ($user->exists) @method('PUT') @endif
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="label">Nama</label><input name="name" value="{{ old('name', $user->name) }}" class="input" required></div>
        <div><label class="label">NIP</label><input name="nip" value="{{ old('nip', $user->nip) }}" class="input"></div>
        <div><label class="label">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="input" required></div>
        <div><label class="label">No. HP</label><input name="phone" value="{{ old('phone', $user->phone) }}" class="input"></div>
        <div><label class="label">Divisi / Departemen</label>
            <select name="division_id" class="input"><option value="">-</option>
                @foreach ($divisions as $d)<option value="{{ $d->id }}" @selected(old('division_id', $user->division_id) == $d->id)>{{ $d->name }}</option>@endforeach
            </select></div>
        <div><label class="label">Jam Kerja (Shift)</label>
            <select name="shift_id" class="input"><option value="">-</option>
                @foreach ($shifts as $s)<option value="{{ $s->id }}" @selected(old('shift_id', $user->shift_id) == $s->id)>{{ $s->name }} ({{ substr($s->jam_masuk,0,5) }}-{{ substr($s->jam_pulang,0,5) }})</option>@endforeach
            </select></div>
        <div><label class="label">Role</label>
            <select name="role" class="input">
                <option value="karyawan" @selected(old('role', $user->role) === 'karyawan')>Karyawan</option>
                <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
            </select></div>
        <div><label class="label">Password {{ $user->exists ? '(kosongkan jika tidak diganti)' : '' }}</label>
            @include('partials.password-input', ['name' => 'password', 'required' => ! $user->exists, 'autocomplete' => 'new-password'])</div>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active))> Akun aktif</label>
    <div class="flex gap-2">
        <button class="btn-primary">Simpan</button>
        <a href="{{ route('admin.karyawan.index') }}" class="btn-light">Batal</a>
    </div>
</form>
@endsection
