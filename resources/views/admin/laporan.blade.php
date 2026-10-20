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
    <span class="rounded-xl bg-card px-4 py-2 ring-1 ring-slate-200">Total lembur: <b>{{ \App\Support\Duration::format($overtimeTotal) }}</b></span>
</div>

<div class="card overflow-x-auto p-0">
    <table class="min-w-full divide-y">
        <thead class="bg-surface-low"><tr><th class="th">Tanggal</th><th class="th">Nama</th><th class="th">Divisi</th><th class="th">Masuk</th><th class="th">Pulang</th><th class="th">Status</th><th class="th">Telat</th><th class="th">Lembur</th><th class="th">Verifikasi Wajah</th></tr></thead>
        <tbody class="divide-y">
        @forelse ($rows as $r)
            <tr>
                <td class="td">{{ $r->date->format('d/m/Y') }}</td>
                <td class="td">{{ $r->user->name }}</td>
                <td class="td">{{ $r->user->division->name ?? '-' }}</td>
                <td class="td">{{ $r->check_in ? substr($r->check_in,0,5) : '-' }}</td>
                <td class="td">{{ $r->check_out ? substr($r->check_out,0,5) : '-' }}</td>
                <td class="td">@include('partials.badge', ['s' => $r->status])</td>
                <td class="td">{{ $r->late_minutes ? \App\Support\Duration::format($r->late_minutes) : '-' }}</td>
                <td class="td">{{ $r->overtime_minutes ? \App\Support\Duration::format($r->overtime_minutes) : '-' }}</td>
                <td class="td">
                    <div class="flex items-start gap-2">
                        @if ($r->check_in_photo || $r->check_out_photo)
                            <div class="text-center text-xs text-slate-500" title="Foto profil akun (pembanding)">
                                @include('partials.avatar', ['user' => $r->user, 'size' => 48])
                                Akun
                            </div>
                            <span class="self-center text-slate-300">→</span>
                        @endif
                        @foreach (['masuk' => $r->check_in_photo, 'pulang' => $r->check_out_photo] as $tipe => $foto)
                            @if ($foto)
                                <a href="{{ route('admin.laporan.foto', [$r, $tipe]) }}" target="_blank" title="Foto {{ $tipe }}" class="block text-center text-xs text-slate-500">
                                    <img src="{{ route('admin.laporan.foto', [$r, $tipe]) }}" loading="lazy" alt="Foto {{ $tipe }} {{ $r->user->name }}" class="h-12 w-12 rounded-lg object-cover ring-1 ring-slate-200">
                                    {{ ucfirst($tipe) }}
                                </a>
                            @endif
                        @endforeach
                        @if (! $r->check_in_photo && ! $r->check_out_photo)<span class="text-slate-400">-</span>@endif
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="td py-8 text-center text-slate-400">Tidak ada data pada filter ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $rows->links() }}</div>
@endsection
