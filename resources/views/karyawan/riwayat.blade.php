@extends('layouts.app')
@section('title', 'Riwayat Kehadiran')
@section('content')
<form class="mb-4 flex items-end gap-2">
    <div><label class="label">Bulan</label><input type="month" name="bulan" value="{{ $month }}" class="input"></div>
    <button class="btn-primary">Tampilkan</button>
</form>
<div class="card overflow-x-auto p-0">
    <table class="min-w-full divide-y">
        <thead class="bg-surface-low"><tr><th class="th">Tanggal</th><th class="th">Masuk</th><th class="th">Pulang</th><th class="th">Status</th><th class="th">Terlambat</th><th class="th">Lembur</th></tr></thead>
        <tbody class="divide-y">
        @forelse ($rows as $r)
            <tr>
                <td class="td">{{ $r->date->translatedFormat('D, d M Y') }}</td>
                <td class="td">{{ $r->check_in ? substr($r->check_in,0,5) : '-' }}</td>
                <td class="td">{{ $r->check_out ? substr($r->check_out,0,5).($r->check_in && $r->check_out < $r->check_in ? ' (+1)' : '') : '-' }}</td>
                <td class="td">@include('partials.badge', ['s' => $r->status])</td>
                <td class="td">{{ $r->late_minutes ? \App\Support\Duration::format($r->late_minutes) : '-' }}</td>
                <td class="td">{{ $r->overtime_minutes ? \App\Support\Duration::format($r->overtime_minutes) : '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="td py-8 text-center text-slate-400">Belum ada data pada bulan ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
