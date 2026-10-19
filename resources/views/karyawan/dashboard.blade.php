@extends('layouts.app')
@section('title', 'Presensi Hari Ini')
@section('content')
<div class="grid gap-6 md:grid-cols-2">
    <div class="card text-center">
        <h2>QR Code Presensi Kamu</h2>
        <p class="mb-4 text-sm text-slate-500">Tunjukkan ke scanner presensi di kantor untuk absen masuk &amp; pulang. QR diperbarui otomatis tiap 30 detik.</p>

        <div id="qrWrap" class="flex justify-center {{ $qr ? '' : 'hidden' }}"><div id="qrcode" class="rounded-xl border p-4"></div></div>
        <div id="qrLocked" class="rounded-xl bg-error-container px-4 py-6 text-sm text-error-on {{ $qr ? 'hidden' : '' }}">
            <b>Kamu tidak terhubung ke jaringan kantor.</b><br>
            Sambungkan HP/laptop ke wifi kantor, QR akan muncul otomatis.
        </div>

        <p id="qrInfo" class="mt-2 text-sm text-slate-400"></p>
        <p class="mt-2 text-sm text-slate-400">{{ now()->translatedFormat('l, d F Y') }} &middot; Shift {{ $shift->name }} ({{ substr($shift->jam_masuk,0,5) }} - {{ substr($shift->jam_pulang,0,5) }})</p>
    </div>

    <div class="space-y-4">
        <div class="card">
            <h2 class="mb-4 font-semibold">Status Hari Ini</h2>
            @if ($leave)
                <div class="mb-2">@include('partials.badge', ['s' => $leave->type])</div>
                <p class="text-sm text-slate-600">Kamu sedang {{ $leave->type }} sampai {{ $leave->end_date->format('d M Y') }}.</p>
            @elseif ($today && $today->check_in)
                <div class="mb-4">@include('partials.badge', ['s' => $today->status])
                    @if ($today->late_minutes) <span class="ml-2 text-sm text-slate-500">(terlambat {{ \App\Support\Duration::format($today->late_minutes) }})</span> @endif
                </div>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-slate-500">Jam Masuk</dt><dd class="text-lg font-semibold">{{ substr($today->check_in,0,5) }}</dd></div>
                    <div class="rounded-xl bg-slate-50 p-4"><dt class="text-slate-500">Jam Keluar</dt><dd class="text-lg font-semibold">{{ $today->check_out ? substr($today->check_out,0,5).($today->check_out < $today->check_in ? ' (+1)' : '') : '-' }}</dd></div>
                </dl>
                @if ($today->check_out && $today->overtime_minutes)
                    <p class="mt-4 rounded-xl bg-amber-50 px-4 py-4 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">Lembur: <b>{{ \App\Support\Duration::format($today->overtime_minutes) }}</b></p>
                @elseif (! $today->check_out && $overtimeLive)
                    <p class="mt-4 rounded-xl bg-amber-50 px-4 py-4 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">Kamu sudah melewati jam pulang ({{ substr($shift->jam_pulang,0,5) }}). Lembur sementara: <b>{{ \App\Support\Duration::format($overtimeLive) }}</b></p>
                @endif
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
    const wrap = document.getElementById('qrWrap'), locked = document.getElementById('qrLocked'), info = document.getElementById('qrInfo');
    let qr = null, isLocked = {{ $qr ? 'false' : 'true' }}, lastOk = Date.now();

    function render(payload) {
        if (!qr) {
            qr = new QRCode(document.getElementById('qrcode'), { text: payload, width: 240, height: 240, correctLevel: QRCode.CorrectLevel.M });
        } else {
            qr.makeCode(payload);
        }
    }
    function setLocked(v) {
        isLocked = v;
        wrap.classList.toggle('hidden', v);
        locked.classList.toggle('hidden', !v);
        if (v) info.textContent = '';
    }

    @if ($qr) render(@json($qr)); @endif

    async function refreshQr() {
        try {
            const r = await fetch(@json(route('karyawan.qr')), { headers: { 'Accept': 'application/json' } });
            if (r.status === 403) { setLocked(true); return; }
            if (!r.ok) return;
            const d = await r.json();
            setLocked(false);
            render(d.payload);
            lastOk = Date.now();
            info.textContent = 'QR diperbarui ' + new Date().toLocaleTimeString('id-ID');
        } catch (e) { /* offline / sesi habis: coba lagi nanti */ }
    }
    // normal: perbarui tiap 30 detik; kalau terkunci (di luar wifi kantor) cek lebih sering
    setInterval(() => { if (isLocked || Date.now() - lastOk >= 30000) refreshQr(); }, 8000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshQr(); });
</script>
@endpush
