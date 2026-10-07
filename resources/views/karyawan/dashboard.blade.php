@extends('layouts.app')
@section('title', 'Presensi Hari Ini')
@section('content')
<div class="grid gap-6 md:grid-cols-2">
    <div class="card text-center">
        <h2>QR Code Presensi Kamu</h2>
        <p class="mb-4 text-sm text-slate-500">Tunjukkan ke scanner presensi di kantor untuk absen masuk &amp; pulang. QR berganti setiap hari.</p>
        <div class="flex justify-center"><div id="qrcode" class="rounded-xl border p-4"></div></div>
        <p class="mt-4 text-sm text-slate-400">{{ now()->translatedFormat('l, d F Y') }} &middot; Shift {{ $shift->name }} ({{ substr($shift->jam_masuk,0,5) }} - {{ substr($shift->jam_pulang,0,5) }})</p>
    </div>

    <div class="space-y-4">
        <div class="card">
            <h2 class="mb-4 font-semibold">Status Hari Ini</h2>
            @if ($leave)
                <div class="mb-2">@include('partials.badge', ['s' => $leave->type])</div>
                <p class="text-sm text-slate-600">Kamu sedang {{ $leave->type }} sampai {{ $leave->end_date->format('d M Y') }}.</p>
            @elseif ($today && $today->check_in)
                <div class="mb-4">@include('partials.badge', ['s' => $today->status])
                    @if ($today->late_minutes) <span class="ml-2 text-sm text-slate-500">({{ $today->late_minutes }} menit)</span> @endif
                </div>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-slate-500">Jam Masuk</dt><dd class="text-lg font-semibold">{{ substr($today->check_in,0,5) }}</dd></div>
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-slate-500">Jam Keluar</dt><dd class="text-lg font-semibold">{{ $today->check_out ? substr($today->check_out,0,5) : '-' }}</dd></div>
                </dl>
            @else
                <p class="text-sm text-slate-600">Kamu belum absen hari ini. Batas masuk: <b>{{ substr($shift->jam_masuk,0,5) }}</b> + toleransi {{ $shift->toleransi_menit }} menit.</p>
            @endif
        </div>
        <a href="{{ route('karyawan.izin') }}" class="btn-light w-full">Ajukan Izin / Sakit / Cuti</a>
    </div>
</div>
@endsection
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById('qrcode'), {
        text: @json($qr), width: 240, height: 240, correctLevel: QRCode.CorrectLevel.M
    });
</script>
@endpush
