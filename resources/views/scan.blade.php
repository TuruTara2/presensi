<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scan QR Presensi - {{ config('app.name') }}</title>
    @include('partials.head')
    <style>
        /* preview kamera apa adanya (tidak di-mirror oleh browser) */
        #reader video { transform: none !important; -webkit-transform: none !important; }
        /* kalau sumber kamera sendiri sudah mirror (driver/webcam), tombol "Balik kamera" membaliknya */
        #reader.flip video { transform: scaleX(-1) !important; -webkit-transform: scaleX(-1) !important; }
    </style>
</head>
<body class="min-h-screen bg-page">
<header class="border-b border-line bg-page">
    <div class="mx-auto flex max-w-page items-center justify-between px-4 py-4 md:px-6">
        <div>
            <div class="font-heading text-[22px] font-semibold leading-tight text-ink">Presensi <span class="text-primary">QR</span></div>
            <div class="text-sm text-slate-500">Absensi karyawan</div>
        </div>
        <div class="flex items-center gap-4">
            @include('partials.theme-toggle')
            @auth
                <a href="{{ route('dashboard') }}" class="btn btn-light">Ke dashboard</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">Masuk</a>
            @endauth
        </div>
    </div>
</header>

<main class="mx-auto max-w-page px-4 py-8 md:px-6">
    <h1 class="h3 mb-2">Scan QR Presensi</h1>
    <p class="mb-6 text-sm text-body">Tunjukkan QR Code dari akun kamu ke kamera. Scan pertama untuk absen masuk, scan kedua untuk absen pulang. Setelah QR terbaca, kamu akan difoto wajah untuk verifikasi.</p>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="mb-4 flex items-center justify-between">
                <h2>Scanner Kamera</h2>
                <div class="text-right">
                    <div id="clock" class="font-mono text-lg font-semibold text-primary"></div>
                    <div id="date" class="text-sm text-slate-500"></div>
                </div>
            </div>
            <div class="relative">
                <div id="reader" class="overflow-hidden rounded-xl bg-slate-100"></div>
                {{-- Tahap 2: foto wajah (muncul setelah QR terbaca) --}}
                <div id="faceStep" style="display:none;position:absolute;inset:0;flex-direction:column;align-items:center;justify-content:space-between;padding:16px;border-radius:12px;background:rgba(0,0,0,.35);color:#fff;text-align:center;z-index:10">
                    <div>
                        <div id="faceName" style="font-weight:600;font-size:18px"></div>
                        <div style="font-size:14px">Hadap kamera, lepas masker/topi. Foto wajah diambil sebentar lagi.</div>
                    </div>
                    <div style="display:flex;align-items:center;justify-content:center">
                        <span id="faceCount" style="font-size:56px;font-weight:700;text-shadow:0 2px 8px rgba(0,0,0,.6)"></span>
                    </div>
                    <div style="font-size:13px;opacity:.9">Posisikan wajah di dalam kotak scanner</div>
                </div>
            </div>
            <p class="mt-4 text-sm text-slate-500">Izinkan akses kamera. Kamera hanya jalan di <b>HTTPS</b> atau <b>localhost</b>.</p>
            <div class="mt-4">
                <label class="label" for="manual">Atau input manual (scanner USB / paste payload)</label>
                <div class="flex gap-2">
                    <input id="manual" class="input" placeholder="ABSEN|1|29735281|...">
                    <button id="manualBtn" class="btn btn-primary" style="height:48px">Kirim</button>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div id="result" class="card hidden"></div>
            <div class="card">
                <h2 class="mb-4">Scan di sesi ini</h2>
                <ul id="recent" class="divide-y divide-line text-sm">
                    <li id="emptyRecent" class="py-4 text-center text-slate-400">Belum ada scan.</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const box = document.getElementById('result');
    let busy = false, lastPayload = '', lastAt = 0;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function tick() {
        const n = new Date();
        document.getElementById('clock').textContent = n.toLocaleTimeString('id-ID');
        document.getElementById('date').textContent = n.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    }
    tick(); setInterval(tick, 1000);

    // Kamera di perangkat scanner ini mengirim gambar mirror (dari driver/webcam), jadi dibalik otomatis.
    // Preview dan foto wajah yang disimpan sama-sama dibalik. Ubah ke false kalau kamera diganti dan gambarnya sudah normal.
    const FLIP_CAMERA = true;
    const flipped = FLIP_CAMERA;
    document.getElementById('reader').classList.toggle('flip', flipped);

    // Ambil 1 frame dari video kamera scanner, kecilkan (maks 640px) lalu jadikan JPEG.
    function capturePhoto() {
        return new Promise(resolve => {
            try {
                const v = document.querySelector('#reader video');
                if (!v || !v.videoWidth) return resolve(null);
                const scale = Math.min(1, 640 / v.videoWidth);
                const c = document.createElement('canvas');
                c.width = Math.round(v.videoWidth * scale);
                c.height = Math.round(v.videoHeight * scale);
                const ctx = c.getContext('2d');
                if (flipped) { ctx.translate(c.width, 0); ctx.scale(-1, 1); } // foto sama dengan yang terlihat di preview
                ctx.drawImage(v, 0, 0, c.width, c.height);
                c.toBlob(b => resolve(b), 'image/jpeg', 0.8);
            } catch (e) { resolve(null); }
        });
    }

    const faceStep = document.getElementById('faceStep');
    const sleep = ms => new Promise(r => setTimeout(r, ms));
    const COUNTDOWN_SECONDS = 2; // hitung mundur sebelum foto wajah (ubah kalau mau lebih lama/singkat)

    // Tahap 2: minta karyawan hadap kamera, hitung mundur 3 detik, lalu ambil foto wajah.
    async function faceCountdown(name, type) {
        const jenis = type === 'masuk' ? ' · Absen MASUK' : (type === 'pulang' ? ' · Absen PULANG' : '');
        document.getElementById('faceName').textContent = 'Halo, ' + name + '!' + jenis;
        faceStep.style.display = 'flex';
        for (let i = COUNTDOWN_SECONDS; i >= 1; i--) {
            document.getElementById('faceCount').textContent = i;
            await sleep(1000);
        }
        document.getElementById('faceCount').textContent = '';
        const photo = await capturePhoto();
        faceStep.style.background = '#fff'; // efek blitz
        await sleep(150);
        return photo;
    }

    async function send(payload) {
        // abaikan QR yang sama selama 8 detik (kamera masih melihat QR yang sama)
        if (busy || (payload === lastPayload && Date.now() - lastAt < 8000)) return;
        busy = true; lastPayload = payload; lastAt = Date.now();
        const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf };
        try {
            // Tahap 1: cek QR
            const vr = await fetch(@json(route('scan.verify')), {
                method: 'POST', headers: { ...headers, 'Content-Type': 'application/json' },
                body: JSON.stringify({ payload })
            });
            const v = await vr.json();
            if (!vr.ok) { show(false, v); return; }

            // Tahap 2: foto wajah
            const photo = await faceCountdown(v.name, v.type);
            if (!photo) { show(false, { message: 'Foto wajah gagal diambil. Coba scan QR lagi.' }); return; }

            // Tahap 3: kirim QR + foto, absen dicatat
            const fd = new FormData();
            fd.append('payload', payload);
            fd.append('photo', photo, 'wajah.jpg');
            const res = await fetch(@json(route('scan.process')), { method: 'POST', headers, body: fd });
            const d = await res.json();
            show(res.ok, d.message ? d : { message: Object.values(d.errors || {}).flat().join(' ') || 'Gagal memproses.' }, photo);
        } catch (e) {
            show(false, { message: 'Gagal terhubung ke server.' });
        } finally {
            faceStep.style.display = 'none'; faceStep.style.background = 'rgba(0,0,0,.35)';
            lastAt = Date.now(); // jangan langsung proses ulang QR yang sama
            setTimeout(() => busy = false, 1500);
        }
    }

    let previewUrl = null;
    function show(ok, d, photo) {
        if (previewUrl) { URL.revokeObjectURL(previewUrl); previewUrl = null; }
        if (photo && ok) previewUrl = URL.createObjectURL(photo);
        box.className = 'card ' + (ok ? 'border-success bg-success/10' : 'border-error bg-error-container');
        box.innerHTML = `<div class="font-heading text-[22px] font-semibold ${ok ? 'text-success-text' : 'text-error-on'}">${ok ? 'Berhasil' : 'Gagal'}</div>
            <div class="mt-2 text-body">${esc(d.message)}</div>
            ${d.time ? `<div class="mt-2 text-sm text-slate-500">Jam ${esc(d.time)}</div>` : ''}
            ${d.overtime_text ? `<div class="mt-2 text-sm font-semibold text-body">Lembur: ${esc(d.overtime_text)}</div>` : ''}
            ${previewUrl ? `<img src="${previewUrl}" alt="Foto wajah" class="mt-3 h-24 w-24 rounded-xl object-cover ring-1 ring-slate-200">` : ''}`;
        if (ok) {
            const item = { name: d.name, type: d.type, time: d.time, status: d.status, day: todayKey() };
            addRecent(item);
            saveRecent(item);
        }
    }

    // ---- Daftar "Scan di sesi ini": disimpan di browser ini, tetap ada setelah refresh / ganti tema.
    // Hanya scan hari ini yang ditampilkan (otomatis reset besok), maksimal 30 item.
    const RECENT_KEY = 'scan_recent_v1', RECENT_MAX = 30;
    const todayKey = () => new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD (waktu lokal)

    function addRecent(d) {
        document.getElementById('emptyRecent')?.remove();
        const li = document.createElement('li');
        li.className = 'flex justify-between py-2';
        li.innerHTML = `<span>${esc(d.name)}</span><span class="text-slate-500">${d.type === 'masuk' ? 'Masuk' : 'Pulang'} ${esc(String(d.time).slice(0,5))} (${esc(d.status)})</span>`;
        document.getElementById('recent').prepend(li);
    }
    function loadRecent() {
        try {
            const list = (JSON.parse(localStorage.getItem(RECENT_KEY)) || []).filter(x => x.day === todayKey());
            list.forEach(addRecent); // urutan lama -> baru, prepend jadi terbaru di atas
        } catch (e) {}
    }
    function saveRecent(item) {
        try {
            const list = (JSON.parse(localStorage.getItem(RECENT_KEY)) || []).filter(x => x.day === todayKey());
            list.push(item);
            localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(-RECENT_MAX)));
        } catch (e) {}
    }
    loadRecent();

    // Scanner dibuat cepat: hanya baca QR, resolusi HD, fokus otomatis, area baca besar, dan fps tinggi.
    const scanner = new Html5Qrcode('reader', {
        formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
        experimentalFeatures: { useBarCodeDetector: true }, // pakai detektor bawaan browser kalau tersedia (jauh lebih cepat)
        verbose: false,
    });
    scanner.start(
        { facingMode: 'environment' },
        {
            fps: 25,
            qrbox: (w, h) => { const s = Math.floor(Math.min(w, h) * 0.85); return { width: s, height: s }; },
            disableFlip: true, // tidak perlu decode ulang versi mirror, setengah beban CPU
            videoConstraints: {
                facingMode: 'environment',
                width: { ideal: 1280 }, height: { ideal: 720 },
                advanced: [{ focusMode: 'continuous' }],
            },
        },
        text => send(text), () => {})
        .catch(err => {
            document.getElementById('reader').innerHTML =
                '<div class="p-6 text-center text-sm text-error">Kamera tidak bisa diakses: ' + esc(err) + '</div>';
        });

    document.getElementById('manualBtn').onclick = () => {
        const v = document.getElementById('manual').value.trim();
        if (v) send(v);
    };
</script>
</body>
</html>
