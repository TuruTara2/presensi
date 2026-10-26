@extends('layouts.app')
@section('title', 'Presensi Manual')
@section('content')
<p class="mb-4 text-sm text-slate-500">Input presensi untuk karyawan yang <b>lupa absen</b>, atau catat <b>izin / sakit / cuti</b>. Semua data presensi bisa diedit atau dihapus dari sini, dan yang diinput atau diubah admin diberi tanda <i>manual</i>.</p>

<div class="mb-4 flex flex-wrap items-end justify-between gap-4">
    <form class="flex flex-wrap items-end gap-2">
        <div><label class="label">Bulan</label><input type="month" name="bulan" value="{{ $bulan }}" class="input" onchange="this.form.submit()"></div>
        <div><label class="label">Cari karyawan</label><input name="q" value="{{ request('q') }}" placeholder="Nama / NIP" class="input"></div>
        <div><label class="label">Status</label>
            <select name="status" class="input" onchange="this.form.submit()"><option value="">Semua</option>
                @foreach (['hadir' => 'Hadir', 'terlambat' => 'Terlambat', 'izin' => 'Izin', 'sakit' => 'Sakit', 'cuti' => 'Cuti'] as $k => $v)
                    <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
                @endforeach
            </select></div>
        <button class="btn-light">Filter</button>
    </form>
    <a href="{{ route('admin.presensi.create') }}" class="btn-primary">+ Tambah Presensi</a>
</div>

<div class="card overflow-x-auto p-0">
    <table class="min-w-full divide-y">
        <thead class="bg-surface-low"><tr><th class="th">Tanggal</th><th class="th">Nama</th><th class="th">Masuk</th><th class="th">Pulang</th><th class="th">Status</th><th class="th">Keterangan</th><th class="th"></th></tr></thead>
        <tbody class="divide-y">
        @forelse ($rows as $r)
            <tr>
                <td class="td whitespace-nowrap">{{ $r->date->format('d/m/Y') }}</td>
                <td class="td"><div class="font-medium">{{ $r->user->name }}</div><div class="text-sm text-slate-400">{{ $r->user->division->name ?? '-' }}</div></td>
                <td class="td">{{ $r->check_in ? substr($r->check_in, 0, 5) : '-' }}</td>
                <td class="td">{{ $r->check_out ? substr($r->check_out, 0, 5) : '-' }}</td>
                <td class="td">@include('partials.badge', ['s' => $r->status])@if ($r->is_manual)<span class="ml-1 text-xs text-slate-400">manual</span>@endif</td>
                <td class="td max-w-xs text-sm text-slate-500">{{ $r->note ?: '-' }}</td>
                <td class="td whitespace-nowrap text-right">
                    <a href="{{ route('admin.presensi.edit', $r) }}" class="btn-light">Edit</a>
                    <form method="POST" action="{{ route('admin.presensi.destroy', $r) }}" class="inline" onsubmit="return confirm('Hapus presensi {{ $r->user->name }} tanggal {{ $r->date->format('d/m/Y') }}?')">
                        @csrf @method('DELETE') <button class="btn-danger">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="td py-8 text-center text-slate-400">Belum ada data presensi pada filter ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $rows->links() }}</div>
@endsection
