@extends('layouts.app')
@section('title', 'Divisi / Departemen')
@section('content')
<form method="POST" action="{{ route('admin.divisi.store') }}" class="card mb-6 flex max-w-xl items-end gap-2">
    @csrf
    <div class="flex-1"><label class="label">Tambah Divisi</label><input name="name" class="input" placeholder="Nama divisi" required></div>
    <button class="btn-primary">Tambah</button>
</form>
<div class="card max-w-xl divide-y p-0">
    @forelse ($items as $d)
        <div class="flex items-center gap-2 p-4">
            <form method="POST" action="{{ route('admin.divisi.update', $d) }}" class="flex flex-1 gap-2">
                @csrf @method('PUT')
                <input name="name" value="{{ $d->name }}" class="input">
                <button class="btn-light">Simpan</button>
            </form>
            <span class="w-20 text-center text-sm text-slate-400">{{ $d->users_count }} orang</span>
            <form method="POST" action="{{ route('admin.divisi.destroy', $d) }}" onsubmit="return confirm('Hapus divisi ini?')">
                @csrf @method('DELETE') <button class="btn-danger">Hapus</button>
            </form>
        </div>
    @empty
        <div class="p-6 text-center text-sm text-slate-400">Belum ada divisi.</div>
    @endforelse
</div>
@endsection
