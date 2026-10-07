@extends('layouts.app')
@section('title', 'Jam Kerja & Toleransi Keterlambatan')
@section('content')
<p class="mb-4 text-sm text-slate-500">Karyawan dianggap <b>terlambat</b> jika scan masuk melewati <i>jam masuk + toleransi</i>.</p>
<form method="POST" action="{{ route('admin.shift.store') }}" class="card mb-6 grid items-end gap-4 sm:grid-cols-5">
    @csrf
    <div class="sm:col-span-2"><label class="label">Nama Shift Baru</label><input name="name" class="input" required></div>
    <div><label class="label">Jam Masuk</label><input type="time" name="jam_masuk" value="08:00" class="input" required></div>
    <div><label class="label">Jam Pulang</label><input type="time" name="jam_pulang" value="17:00" class="input" required></div>
    <div><label class="label">Toleransi (menit)</label><input type="number" name="toleransi_menit" value="15" min="0" class="input" required></div>
    <div class="sm:col-span-5"><button class="btn-primary">Tambah Shift</button></div>
</form>

<div class="space-y-4">
@foreach ($items as $s)
    <div class="card flex flex-wrap items-end gap-4">
        <form method="POST" action="{{ route('admin.shift.update', $s) }}" class="grid flex-1 items-end gap-4 sm:grid-cols-5">
            @csrf @method('PUT')
            <div class="sm:col-span-2"><label class="label">Nama</label><input name="name" value="{{ $s->name }}" class="input" required></div>
            <div><label class="label">Masuk</label><input type="time" name="jam_masuk" value="{{ substr($s->jam_masuk,0,5) }}" class="input" required></div>
            <div><label class="label">Pulang</label><input type="time" name="jam_pulang" value="{{ substr($s->jam_pulang,0,5) }}" class="input" required></div>
            <div><label class="label">Toleransi (mnt)</label><input type="number" name="toleransi_menit" value="{{ $s->toleransi_menit }}" min="0" class="input" required></div>
            <div class="sm:col-span-5 flex items-center gap-2">
                <button class="btn-light">Simpan</button>
                <span class="text-sm text-slate-400">{{ $s->users_count }} karyawan memakai shift ini</span>
            </div>
        </form>
        <form method="POST" action="{{ route('admin.shift.destroy', $s) }}" onsubmit="return confirm('Hapus shift ini?')">
            @csrf @method('DELETE') <button class="btn-danger">Hapus</button>
        </form>
    </div>
@endforeach
</div>
@endsection
