@extends('layouts.app')
@section('title', 'Jaringan Kantor')
@section('content')
@if ($enabled)
    <div class="mb-6 rounded-xl border border-success/30 bg-success/10 px-4 py-4 text-sm text-success-text">
        <b>Validasi IP aktif.</b> Karyawan hanya bisa menampilkan QR presensi saat HP-nya terhubung ke jaringan di bawah ini.
    </div>
@else
    <div class="mb-6 rounded-xl bg-amber-50 px-4 py-4 text-sm text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30">
        <b>Validasi IP nonaktif</b> karena belum ada jaringan aktif. Saat ini karyawan bisa menampilkan QR dari mana saja.
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-3">
    <form method="POST" action="{{ route('admin.jaringan.store') }}" class="card space-y-4 lg:col-span-1">
        @csrf
        <h2>Tambah Jaringan</h2>
        <div class="rounded-xl bg-slate-50 p-4 text-sm">
            IP kamu saat ini: <b>{{ $myIp }}</b>
            <button type="button" class="ml-2 font-semibold text-primary hover:underline" onclick="document.getElementById('cidr').value='{{ $myIp }}'">pakai IP ini</button>
        </div>
        <div><label class="label" for="label">Nama</label><input id="label" name="label" value="{{ old('label') }}" class="input" placeholder="Wifi Kantor Lantai 1" required></div>
        <div><label class="label" for="cidr">IP atau range (CIDR)</label><input id="cidr" name="cidr" value="{{ old('cidr') }}" class="input" placeholder="192.168.1.0/24" required></div>
        <p class="text-sm text-slate-500">
            <b>Server lokal kantor:</b> isi range jaringan lokal, mis. <code>192.168.1.0/24</code>.<br>
            <b>Hosting online:</b> isi IP publik kantor (cek di whatismyip.com), mis. <code>103.12.34.56</code>.
        </p>
        <button class="btn btn-primary w-full">Tambah</button>
    </form>

    <div class="card overflow-x-auto p-0 lg:col-span-2">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-low"><tr><th class="th">Nama</th><th class="th">IP / Range</th><th class="th">Status</th><th class="th"></th></tr></thead>
            <tbody class="divide-y divide-line">
            @forelse ($items as $n)
                <tr>
                    <td class="td">{{ $n->label }}</td>
                    <td class="td font-mono">{{ $n->cidr }}</td>
                    <td class="td">{!! $n->is_active ? '<span class="text-success-text font-semibold">Aktif</span>' : '<span class="text-slate-400">Nonaktif</span>' !!}</td>
                    <td class="td whitespace-nowrap text-right">
                        <form method="POST" action="{{ route('admin.jaringan.toggle', $n) }}" class="inline">@csrf @method('PUT')
                            <button class="btn btn-light">{{ $n->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>
                        <form method="POST" action="{{ route('admin.jaringan.destroy', $n) }}" class="inline" onsubmit="return confirm('Hapus jaringan ini?')">@csrf @method('DELETE')
                            <button class="btn btn-danger">Hapus</button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="td py-8 text-center text-slate-400">Belum ada jaringan kantor.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-6">
    <div class="mb-4">
        <h2 class="font-semibold">Perangkat karyawan di jaringan</h2>
        <p class="mt-1 text-sm text-slate-500">
            Perangkat tercatat saat karyawan login dan halaman website terbuka. Status online berarti heartbeat diterima dari jaringan aktif dalam 1 menit terakhir; daftar diperbarui otomatis tiap 30 detik.
        </p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-line">
            <thead class="bg-surface-low">
                <tr>
                    <th class="th">Karyawan</th>
                    <th class="th">Perangkat</th>
                    <th class="th">IP terakhir</th>
                    <th class="th">Status</th>
                    <th class="th">Terakhir terlihat</th>
                </tr>
            </thead>
            <tbody id="networkPresenceRows" class="divide-y divide-line">
                @forelse ($presences as $presence)
                    @php($online = $enabled && $presence->last_seen_at->greaterThanOrEqualTo($onlineSince))
                    <tr>
                        <td class="td">{{ $presence->user->name }}</td>
                        <td class="td">{{ $presence->user->device_name ?: 'Perangkat tidak dikenal' }}</td>
                        <td class="td font-mono">{{ $presence->ip_address }}</td>
                        <td class="td">
                            @if ($online)
                                <span class="font-semibold text-success-text">Online</span>
                            @else
                                <span class="text-slate-400">Offline</span>
                            @endif
                        </td>
                        <td class="td">{{ $presence->last_seen_at->format('d M Y H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td class="td py-8 text-center text-slate-400" colspan="5">Belum ada perangkat yang terdeteksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    (function () {
        var body = document.getElementById('networkPresenceRows');
        var url = @json(route('admin.jaringan.presence'));
        var lastFailure = false;

        function cell(row, value, className) {
            var td = document.createElement('td');
            td.className = className || 'td';
            td.textContent = value;
            row.appendChild(td);
            return td;
        }

        function render(rows) {
            body.replaceChildren();
            if (!rows.length) {
                var emptyRow = document.createElement('tr');
                var emptyCell = cell(emptyRow, 'Belum ada perangkat yang terdeteksi.', 'td py-8 text-center text-slate-400');
                emptyCell.colSpan = 5;
                body.appendChild(emptyRow);
                return;
            }

            rows.forEach(function (item) {
                var row = document.createElement('tr');
                cell(row, item.name);
                cell(row, item.device);
                cell(row, item.ip_address, 'td font-mono');

                var status = cell(row, item.online ? 'Online' : 'Offline');
                status.className = item.online ? 'td font-semibold text-success-text' : 'td text-slate-400';

                var date = new Date(item.last_seen_at);
                cell(row, new Intl.DateTimeFormat('id-ID', {
                    dateStyle: 'medium',
                    timeStyle: 'medium'
                }).format(date));
                body.appendChild(row);
            });
        }

        async function refresh() {
            try {
                var response = await fetch(url, {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });
                if (!response.ok) throw new Error('HTTP ' + response.status);
                render(await response.json());
                lastFailure = false;
            } catch (error) {
                if (!lastFailure) console.error('Gagal memperbarui daftar perangkat jaringan:', error);
                lastFailure = true;
            }
        }

        window.setInterval(refresh, 30000);
    })();
</script>

<p class="mt-6 text-sm text-slate-500">Catatan: kalau aplikasi berada di belakang proxy (mis. Cloudflare), isi <code>TRUSTED_PROXIES</code> di file <code>.env</code> supaya IP asli karyawan terbaca.</p>
@endsection
