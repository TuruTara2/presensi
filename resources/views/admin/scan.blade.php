@extends('layouts.app')
@section('title', 'Scan QR Presensi')
@section('content')
<div class="grid gap-6 lg:grid-cols-2">
    <div class="card">
        <div class="mb-4 flex items-center justify-between">
            <h2>Scanner Kamera</h2>
            <div id="clock" class="font-mono text-lg font-semibold text-primary"></div>
        </div>
        <div id="reader" class="overflow-hidden rounded-xl bg-slate-100"></div>
        <p class="mt-4 text-sm text-slate-500">Izinkan akses kamera. Kamera hanya jalan di <b>HTTPS</b> atau <b>localhost</b>.</p>
        <div class="mt-4">
            <label class="label">Atau input manual (isi dari scanner USB / paste payload)</label>
            <div class="flex gap-2">
                <input id="manual" class="input" placeholder="ABSEN|1|2026-01-01|...">
                <button id="manualBtn" class="btn-primary">Kirim</button>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div id="result" class="card hidden"></div>
        <div class="card">
            <h2 class="mb-4 font-semibold">Aktivitas Terakhir Hari Ini</h2>
            <ul id="recent" class="divide-y text-sm">
                @forelse ($recent as $a)
                    <li class="flex justify-between py-2">
                        <span>{{ $a->user->name }}</span>
                        <span class="text-slate-500">
                            @if($a->check_out) Pulang {{ substr($a->check_out,0,5) }} @else Masuk {{ substr($a->check_in,0,5) }} @endif
                            @include('partials.badge', ['s' => $a->status])
                        </span>
                    </li>
                @empty
                    <li id="emptyRecent" class="py-4 text-center text-slate-400">Belum ada scan.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
@push('head')
<style>
    /* paksa preview kamera TIDAK mirror */
    #reader video { transform: none !important; -webkit-transform: none !important; }
</style>
@endpush
//yay
//supaya
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const box = document.getElementById('result');
    let busy = false, lastPayload = '', lastAt = 0;

    setInterval(() => document.getElementById('clock').textContent = new Date().toLocaleTimeString('id-ID'), 1000);

    async function send(payload) {
        // abaikan QR yang sama selama 8 detik (kamera masih melihat QR yang sama)
        if (busy || (payload === lastPayload && Date.now() - lastAt < 8000)) return;
        busy = true; lastPayload = payload; lastAt = Date.now();
        try {
            const res = await fetch(@json(route('admin.scan.process')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ payload })
            });
            const d = await res.json();
            show(res.ok, d);
        } catch (e) {
            show(false, { message: 'Gagal terhubung ke server.' });
        }
        setTimeout(() => busy = false, 2500); // jeda supaya QR yang sama tidak terbaca berulang
    }

    function show(ok, d) {
        box.className = 'card ring-2 ' + (ok ? 'ring-green-400 bg-green-50' : 'ring-red-400 bg-red-50');
        box.innerHTML = `<div class="text-lg font-bold ${ok ? 'text-green-700' : 'text-red-700'}">${ok ? '✔ Berhasil' : '✖ Gagal'}</div>
            <div class="text-sm text-slate-700">${d.message ?? ''}</div>
            ${d.time ? `<div class="mt-1 text-sm text-slate-500">Jam ${d.time}</div>` : ''}`;
        if (ok) {
            document.getElementById('emptyRecent')?.remove();
            const li = document.createElement('li');
            li.className = 'flex justify-between py-2';
            li.innerHTML = `<span>${d.name}</span><span class="text-slate-500">${d.type === 'masuk' ? 'Masuk' : 'Pulang'} ${d.time.slice(0,5)} (${d.status})</span>`;
            document.getElementById('recent').prepend(li);
        }
    }

    const scanner = new Html5Qrcode('reader');
    scanner.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 240, height: 240 } },
        text => send(text), () => {})
        .catch(err => {
            document.getElementById('reader').innerHTML =
                '<div class="p-6 text-center text-sm text-primary">Kamera tidak bisa diakses: ' + err + '</div>';
        });

    document.getElementById('manualBtn').onclick = () => {
        const v = document.getElementById('manual').value.trim();
        if (v) send(v);
    };
</script>
@endpush
