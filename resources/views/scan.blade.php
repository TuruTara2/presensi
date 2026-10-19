<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scan QR Presensi - {{ config('app.name') }}</title>
    @include('partials.head')
    <style>
        /* paksa preview kamera TIDAK mirror */
        #reader video { transform: none !important; -webkit-transform: none !important; }
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
    <p class="mb-6 text-sm text-body">Tunjukkan QR Code dari akun kamu ke kamera. Scan pertama untuk absen masuk, scan kedua untuk absen pulang.</p>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="mb-4 flex items-center justify-between">
                <h2>Scanner Kamera</h2>
                <div class="text-right">
                    <div id="clock" class="font-mono text-lg font-semibold text-primary"></div>
                    <div id="date" class="text-sm text-slate-500"></div>
                </div>
            </div>
            <div id="reader" class="overflow-hidden rounded-xl bg-slate-100"></div>
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

    async function send(payload) {
        // abaikan QR yang sama selama 8 detik (kamera masih melihat QR yang sama)
        if (busy || (payload === lastPayload && Date.now() - lastAt < 8000)) return;
        busy = true; lastPayload = payload; lastAt = Date.now();
        try {
            const res = await fetch(@json(route('scan.process')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ payload })
            });
            const d = await res.json();
            show(res.ok, d);
        } catch (e) {
            show(false, { message: 'Gagal terhubung ke server.' });
        }
        setTimeout(() => busy = false, 2500);
    }

    function show(ok, d) {
        box.className = 'card ' + (ok ? 'border-success bg-success/10' : 'border-error bg-error-container');
        box.innerHTML = `<div class="font-heading text-[22px] font-semibold ${ok ? 'text-success-text' : 'text-error-on'}">${ok ? 'Berhasil' : 'Gagal'}</div>
            <div class="mt-2 text-body">${esc(d.message)}</div>
            ${d.time ? `<div class="mt-2 text-sm text-slate-500">Jam ${esc(d.time)}</div>` : ''}
            ${d.overtime_text ? `<div class="mt-2 text-sm font-semibold text-body">Lembur: ${esc(d.overtime_text)}</div>` : ''}`;
        if (ok) {
            document.getElementById('emptyRecent')?.remove();
            const li = document.createElement('li');
            li.className = 'flex justify-between py-2';
            li.innerHTML = `<span>${esc(d.name)}</span><span class="text-slate-500">${d.type === 'masuk' ? 'Masuk' : 'Pulang'} ${esc(d.time.slice(0,5))} (${esc(d.status)})</span>`;
            document.getElementById('recent').prepend(li);
        }
    }

    const scanner = new Html5Qrcode('reader');
    scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 240, height: 240 } },
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
