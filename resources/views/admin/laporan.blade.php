@extends('layouts.app')
@section('title', 'Rekapitulasi & Export Laporan')
@section('content')
<form class="card mb-6 flex flex-wrap items-end gap-4">
    <div><label class="label">Dari</label><input type="date" name="from" value="{{ $from }}" class="input"></div>
    <div><label class="label">Sampai</label><input type="date" name="to" value="{{ $to }}" class="input"></div>
    <div><label class="label">atau Bulan</label><input type="month" name="bulan" value="{{ request('bulan') }}" class="input"></div>
    <div><label class="label">Divisi</label>
        <select name="division_id" class="input"><option value="">Semua</option>
            @foreach ($divisions as $d)<option value="{{ $d->id }}" @selected(request('division_id') == $d->id)>{{ $d->name }}</option>@endforeach
        </select></div>
    <button class="btn-primary">Tampilkan</button>
    <a href="{{ route('admin.laporan.export', request()->query()) }}" class="btn-light">Export CSV (Excel)</a>
</form>

<div class="mb-4 flex flex-wrap gap-2 text-sm">
    @foreach (['hadir', 'terlambat', 'izin', 'sakit', 'cuti'] as $st)
        <span class="rounded-xl bg-card px-4 py-2 ring-1 ring-slate-200">{{ ucfirst($st) }}: <b>{{ $sum[$st] ?? 0 }}</b></span>
    @endforeach
</div>

<div class="card overflow-x-auto p-0">
    <table class="min-w-full divide-y">
        <thead class="bg-surface-low"><tr><th class="th">Tanggal</th><th class="th">Nama</th><th class="th">Divisi</th><th class="th">Masuk</th><th class="th">Pulang</th><th class="th">Status</th><th class="th">Telat</th></tr></thead>
        <tbody class="divide-y">
        @forelse ($rows as $r)
            <tr>
                <td class="td">{{ $r->date->format('d/m/Y') }}</td>
                <td class="td">{{ $r->user->name }}</td>
                <td class="td">{{ $r->user->division->name ?? '-' }}</td>
                <td class="td">{{ $r->check_in ? substr($r->check_in,0,5) : '-' }}</td>
                <td class="td">{{ $r->check_out ? substr($r->check_out,0,5) : '-' }}</td>
                <td class="td">@include('partials.badge', ['s' => $r->status])</td>
                <td class="td">{{ $r->late_minutes ? $r->late_minutes.' mnt' : '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="td py-8 text-center text-slate-400">Tidak ada data pada filter ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $rows->links() }}</div>
@endsection
