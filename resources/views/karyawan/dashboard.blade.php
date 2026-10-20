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
        <div class="card" id="statusCard">
            @include('karyawan.partials.status')
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

    // ---- Status Hari Ini: ambil ulang dari server (database), jadi otomatis ikut update setelah scan
    const statusCard = document.getElementById('statusCard');
    async function refreshStatus() {
        try {
            const r = await fetch(@json(route('karyawan.status')), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' });
            if (!r.ok) return;
            const html = await r.text();
            if (html.trim() !== statusCard.innerHTML.trim()) statusCard.innerHTML = html;
        } catch (e) { /* offline / sesi habis: coba lagi nanti */ }
    }
    setInterval(refreshStatus, 10000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) refreshStatus(); });
    window.addEventListener('pageshow', refreshStatus); // balik via tombol back / bfcache
</script>
@endpush
