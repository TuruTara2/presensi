@extends('layouts.app')
@section('title', 'Divisi / Departemen')
@push('head')
<style>summary::-webkit-details-marker { display: none; }</style>
@endpush
@section('content')

{{-- Ringkasan --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="card"><div class="text-sm text-slate-500">Total divisi</div><div class="font-heading text-3xl font-semibold text-ink">{{ $items->count() }}</div></div>
    <div class="card"><div class="text-sm text-slate-500">Karyawan aktif</div><div class="font-heading text-3xl font-semibold text-ink">{{ $totalEmployees }}</div></div>
    <div class="card"><div class="text-sm text-slate-500">Belum punya divisi</div><div class="font-heading text-3xl font-semibold {{ $unassigned ? 'text-amber-600' : 'text-success-text' }}">{{ $unassigned }}</div></div>
</div>

{{-- Tambah divisi --}}
<form method="POST" action="{{ route('admin.divisi.store') }}" class="card mb-6 flex flex-wrap items-end gap-4">
    @csrf
    <div class="min-w-[240px] flex-1">
        <label class="label" for="newDivision">Tambah divisi baru</label>
        <input id="newDivision" name="name" class="input" placeholder="Contoh: Marketing" required>
    </div>
    <button class="btn btn-lg btn-primary">+ Tambah Divisi</button>
</form>

{{-- Daftar divisi --}}
@if ($items->isEmpty())
    <div class="card py-12 text-center">
        <div class="font-heading text-[22px] font-semibold text-ink">Belum ada divisi</div>
        <p class="mt-2 text-sm text-slate-500">Tambahkan divisi pertama lewat form di atas.</p>
    </div>
@else
<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @foreach ($items as $d)
        @php
            $members = $d->users;
            $total = $members->count();
            $present = $members->whereIn('id', $presentIds)->count();
            $pct = $total ? round($present / $total * 100) : 0;
            $mono = collect(preg_split('/\s+/', trim($d->name)))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('');
        @endphp
        <div class="card flex flex-col">
            <div class="flex items-start gap-4">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 font-heading text-lg font-semibold text-primary">{{ $mono }}</span>
                <div class="min-w-0 flex-1">
                    <h2 class="truncate">{{ $d->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $total }} karyawan</p>
                </div>
            </div>

            <div class="mt-4 min-h-[32px]">
                @if ($total)
                    <div class="flex items-center">
                        <div class="flex -space-x-2">
                            @foreach ($members->take(5) as $m)
                                <span class="rounded-full ring-2 ring-card" title="{{ $m->name }}">@include('partials.avatar', ['user' => $m, 'size' => 32])</span>
                            @endforeach
                            @if ($total > 5)
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-sm font-semibold text-body ring-2 ring-card">+{{ $total - 5 }}</span>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-sm text-slate-400">Belum ada anggota.</p>
                @endif
            </div>

            <div class="mt-4">
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="text-slate-500">Hadir hari ini</span>
                    <span class="font-semibold text-ink">{{ $present }}/{{ $total }}</span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-slate-100">
                    <div class="h-2 rounded-full bg-success" style="width: {{ $pct }}%"></div>
                </div>
            </div>

            <div class="mt-6 flex items-start gap-2 border-t border-line pt-4">
                <details class="flex-1">
                    <summary class="btn btn-light w-full cursor-pointer list-none">Ubah nama</summary>
                    <form method="POST" action="{{ route('admin.divisi.update', $d) }}" class="mt-4 space-y-2">
                        @csrf @method('PUT')
                        <input name="name" value="{{ $d->name }}" class="input" required>
                        <button class="btn btn-primary w-full">Simpan</button>
                    </form>
                </details>
                <form method="POST" action="{{ route('admin.divisi.destroy', $d) }}" onsubmit="return confirm('Hapus divisi ini? Karyawan di dalamnya akan menjadi tanpa divisi.')">
                    @csrf @method('DELETE')
                    <button class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endif
@endsection
