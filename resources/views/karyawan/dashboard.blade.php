@extends('layouts.app')
@section('title', 'Presensi Hari Ini')
@section('content')
<div class="grid gap-6 md:grid-cols-2">
    <div class="card text-center">
        <h2>QR Code Presensi Kamu</h2>
        <p class="mb-4 text-sm text-slate-500">Pilih <b>QR Masuk</b> saat tiba dan <b>QR Pulang</b> saat pulang, lalu tunjukkan ke scanner presensi. QR diperbarui otomatis tiap 30 detik.</p>

        <div class="mb-4 flex justify-center gap-2">
            <button type="button" data-qr-type="masuk" class="qr-tab {{ $qrType === 'masuk' ? 'btn-primary' : 'btn-light' }}">QR Masuk</button>
            <button type="button" data-qr-type="pulang" class="qr-tab {{ $qrType === 'pulang' ? 'btn-primary' : 'btn-light' }}">QR Pulang</button>
        </div>

        <div id="qrWrap" class="flex justify-center {{ $qr ? '' : 'hidden' }}">{{-- Latar QR selalu putih + pinggiran lebar (quiet zone), supaya tetap terbaca scanner di mode gelap --}}
            <div id="qrcode" onclick="openQrFull()" title="Ketuk untuk layar penuh" style="background:#fff;padding:clamp(20px,8vw,40px);border-radius:16px;line-height:0;cursor:zoom-in;max-width:100%"></div></div>
        <p id="qrHint" class="mt-2 text-xs text-slate-400 {{ $qr ? '' : 'hidden' }}">Susah dipindai? Ketuk QR untuk tampilan layar penuh.</p>

        {{-- Layar penuh putih: QR besar di atas latar putih, paling mudah dibaca kamera (terutama saat mode gelap) --}}
        <div id="qrFull" onclick="closeQrFull()" style="display:none;position:fixed;inset:0;z-index:50;background:#fff;flex-direction:column;align-items:center;justify-content:center;cursor:zoom-out">
            <div id="qrBig" style="line-height:0"></div>
            <p style="margin-top:24px;font-size:14px;color:#444">Ketuk di mana saja untuk menutup</p>
        </div>
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
    let qr = null, isLocked = {{ $qr ? 'false' : 'true' }}, lastOk = Date.now(), qrType = @json($qrType);

    let currentPayload = '', big = null;
    const qrFull = document.getElementById('qrFull'), qrBig = document.getElementById('qrBig');
    function renderBig() {
        const size = Math.floor(Math.min(window.innerWidth, window.innerHeight) * 0.78);
        qrBig.innerHTML = '';
        big = new QRCode(qrBig, { text: currentPayload, width: size, height: size, correctLevel: QRCode.CorrectLevel.M });
    }
    function openQrFull() { if (!currentPayload) return; qrFull.style.display = 'flex'; renderBig(); }
    function closeQrFull() { qrFull.style.display = 'none'; }

    function render(payload) {
        currentPayload = payload;
        if (qrFull.style.display === 'flex') renderBig(); // QR di layar penuh ikut diperbarui
        if (!qr) {
            qr = new QRCode(document.getElementById('qrcode'), { text: payload, width: 240, height: 240, correctLevel: QRCode.CorrectLevel.M });
        } else {
            qr.makeCode(payload);
        }
    }
    function setLocked(v) {
        isLocked = v;
        wrap.classList.toggle('hidden', v);
        document.getElementById('qrHint').classList.toggle('hidden', v);
        if (v) closeQrFull();
        locked.classList.toggle('hidden', !v);
        if (v) info.textContent = '';
    }

    @if ($qr) render(@json($qr)); @endif

    async function refreshQr() {
        try {
            const r = await fetch(@json(route('karyawan.qr')) + '?type=' + qrType, { headers: { 'Accept': 'application/json' } });
            if (r.status === 403) { setLocked(true); return; }
            if (!r.ok) return;
            const d = await r.json();
            setLocked(false);
            render(d.payload);
            lastOk = Date.now();
            info.textContent = 'QR diperbarui ' + new Date().toLocaleTimeString('id-ID');
        } catch (e) { /* offline / sesi habis: coba lagi nanti */ }
    }
    // Tab QR Masuk / QR Pulang
    document.querySelectorAll('.qr-tab').forEach(btn => btn.addEventListener('click', () => {
        qrType = btn.dataset.qrType;
        document.querySelectorAll('.qr-tab').forEach(b => {
            const on = b.dataset.qrType === qrType;
            b.classList.toggle('btn-primary', on);
            b.classList.toggle('btn-light', !on);
        });
        refreshQr();
    }));
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
