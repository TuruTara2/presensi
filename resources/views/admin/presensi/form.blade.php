@extends('layouts.app')
@section('title', $att->exists ? 'Edit Presensi' : 'Tambah Presensi Manual')
@section('content')
@php
    $type = old('type', $att->exists ? (in_array($att->status, ['hadir', 'terlambat']) ? 'hadir' : $att->status) : 'hadir');
@endphp
<form method="POST" action="{{ $att->exists ? route('admin.presensi.update', $att) : route('admin.presensi.store') }}" class="card max-w-2xl space-y-6">
    @csrf
    @if ($att->exists) @method('PUT') @endif

    @if ($att->exists)
        <div class="grid gap-4 sm:grid-cols-2">
            <div><div class="label">Karyawan</div><div class="font-medium">{{ $att->user->name }} <span class="text-sm font-normal text-slate-400">{{ $att->user->division->name ?? '' }}</span></div></div>
            <div><div class="label">Tanggal</div><div class="font-medium">{{ $att->date->format('d/m/Y') }}</div></div>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="label" for="user_id">Karyawan</label>
                <select id="user_id" name="user_id" class="input" required>
                    <option value="">- Pilih karyawan -</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" @selected(old('user_id') == $u->id)>{{ $u->name }}{{ $u->division ? ' · '.$u->division->name : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="date">Tanggal</label>
                <input id="date" type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="input" required>
            </div>
        </div>
    @endif

    <div>
        <label class="label" for="type">Jenis</label>
        <select id="type" name="type" class="input" required>
            @foreach (['hadir' => 'Hadir (lupa absen / input manual)', 'izin' => 'Izin', 'sakit' => 'Sakit', 'cuti' => 'Cuti'] as $k => $v)
                <option value="{{ $k }}" @selected($type === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </div>

    <div id="timeFields" class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="label" for="check_in">Jam masuk</label>
            <input id="check_in" type="time" name="check_in" value="{{ old('check_in', $att->check_in ? substr($att->check_in, 0, 5) : '') }}" class="input">
        </div>
        <div>
            <label class="label" for="check_out">Jam pulang <span class="font-normal text-slate-400">(kosongkan kalau belum pulang)</span></label>
            <input id="check_out" type="time" name="check_out" value="{{ old('check_out', $att->check_out ? substr($att->check_out, 0, 5) : '') }}" class="input">
        </div>
        <p class="text-sm text-slate-500 sm:col-span-2">Status <b>Hadir</b> atau <b>Terlambat</b>, menit telat, dan lembur dihitung otomatis dari jam kerja dan toleransi shift karyawan.</p>
    </div>

    <div>
        <label class="label" for="note">Keterangan <span class="font-normal text-slate-400">(opsional)</span></label>
        <input id="note" name="note" maxlength="300" value="{{ old('note', $att->note) }}" class="input" placeholder="Contoh: lupa scan, scanner error / surat dokter">
    </div>

    <div class="flex gap-2">
        <button class="btn-primary">Simpan</button>
        <a href="{{ route('admin.presensi.index') }}" class="btn-light">Batal</a>
    </div>
</form>
@endsection
@push('scripts')
<script>
    // Jam masuk/pulang hanya relevan untuk status Hadir
    const typeSel = document.getElementById('type'), timeBox = document.getElementById('timeFields');
    function toggleTime() { timeBox.style.display = typeSel.value === 'hadir' ? '' : 'none'; }
    typeSel.addEventListener('change', toggleTime); toggleTime();
</script>
@endpush
