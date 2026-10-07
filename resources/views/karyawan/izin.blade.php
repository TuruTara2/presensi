@extends('layouts.app')
@section('title', 'Izin / Sakit / Cuti')
@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <form method="POST" action="{{ route('karyawan.izin.store') }}" enctype="multipart/form-data" class="card space-y-4 lg:col-span-1">
        @csrf
        <h2>Form Pengajuan</h2>
        <div><label class="label">Jenis</label>
            <select name="type" class="input">
                @foreach (['izin' => 'Izin', 'sakit' => 'Sakit', 'cuti' => 'Cuti'] as $k => $v)
                    <option value="{{ $k }}" @selected(old('type') === $k)>{{ $v }}</option>
                @endforeach
            </select></div>
        <div class="grid grid-cols-2 gap-4">
            <div><label class="label">Dari</label><input type="date" name="start_date" value="{{ old('start_date', today()->toDateString()) }}" class="input" required></div>
            <div><label class="label">Sampai</label><input type="date" name="end_date" value="{{ old('end_date', today()->toDateString()) }}" class="input" required></div>
        </div>
        <div><label class="label">Alasan</label><textarea name="reason" rows="3" class="input" required>{{ old('reason') }}</textarea></div>
        <div><label class="label">Lampiran (JPG/PNG/PDF, maks 2MB)</label><input type="file" name="attachment" class="input"></div>
        <button class="btn-primary w-full">Kirim Pengajuan</button>
    </form>

    <div class="card overflow-x-auto p-0 lg:col-span-2">
        <table class="min-w-full divide-y">
            <thead class="bg-surface-low"><tr><th class="th">Jenis</th><th class="th">Tanggal</th><th class="th">Status</th><th class="th">Catatan Admin</th></tr></thead>
            <tbody class="divide-y">
            @forelse ($items as $i)
                <tr>
                    <td class="td">@include('partials.badge', ['s' => $i->type])</td>
                    <td class="td">{{ $i->start_date->format('d M') }} - {{ $i->end_date->format('d M Y') }}
                        @if ($i->attachment)<a class="ml-1 text-primary underline" target="_blank" href="{{ Storage::url($i->attachment) }}">lampiran</a>@endif
                        <div class="text-sm text-slate-400">{{ $i->reason }}</div></td>
                    <td class="td">@include('partials.badge', ['s' => $i->status])</td>
                    <td class="td">{{ $i->admin_note ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="td py-8 text-center text-slate-400">Belum ada pengajuan.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
