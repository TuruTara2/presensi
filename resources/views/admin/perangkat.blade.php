@extends('layouts.app')
@section('title', 'Perangkat Karyawan')
@section('content')
<p class="mb-6 text-sm text-slate-500">Setiap akun karyawan terikat ke <b>satu perangkat</b> (browser) yang dipakai saat login pertama. Login dari perangkat lain ditolak dan masuk ke daftar permintaan di bawah.</p>

<div class="card mb-6 p-0">
    <div class="px-6 pt-6"><h2>Permintaan Perangkat Baru ({{ $requests->count() }})</h2></div>
    <div class="overflow-x-auto">
    <table class="mt-4 min-w-full divide-y divide-line">
        <thead class="bg-surface-low"><tr><th class="th">Karyawan</th><th class="th">Perangkat</th><th class="th">IP</th><th class="th">Waktu</th><th class="th"></th></tr></thead>
        <tbody class="divide-y divide-line">
        @forelse ($requests as $r)
            <tr>
                <td class="td"><b>{{ $r->user->name }}</b><div class="text-sm text-slate-400">{{ $r->user->nip }}</div></td>
                <td class="td">{{ \App\Support\Device::label($r->user_agent) }}</td>
                <td class="td font-mono">{{ $r->ip }}</td>
                <td class="td">{{ $r->created_at->translatedFormat('d M H:i') }}</td>
                <td class="td whitespace-nowrap text-right">
                    <form method="POST" action="{{ route('admin.perangkat.decide', $r) }}" class="inline" onsubmit="return confirm('Setujui perangkat ini? Perangkat lama {{ $r->user->name }} akan otomatis logout.')">@csrf @method('PUT')
                        <button name="status" value="disetujui" class="btn btn-primary">Setujui</button></form>
                    <form method="POST" action="{{ route('admin.perangkat.decide', $r) }}" class="inline">@csrf @method('PUT')
                        <button name="status" value="ditolak" class="btn btn-danger">Tolak</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="td py-8 text-center text-slate-400">Tidak ada permintaan.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>

<div class="card p-0">
    <div class="px-6 pt-6"><h2>Perangkat Terdaftar</h2></div>
    <div class="overflow-x-auto">
    <table class="mt-4 min-w-full divide-y divide-line">
        <thead class="bg-surface-low"><tr><th class="th">Karyawan</th><th class="th">Perangkat</th><th class="th">Terdaftar</th><th class="th"></th></tr></thead>
        <tbody class="divide-y divide-line">
        @foreach ($users as $u)
            <tr>
                <td class="td"><b>{{ $u->name }}</b><div class="text-sm text-slate-400">{{ $u->nip }}</div></td>
                <td class="td">{{ $u->device_name ?? 'Belum ada (terdaftar saat login pertama)' }}</td>
                <td class="td">{{ $u->device_bound_at?->translatedFormat('d M Y H:i') ?? '-' }}</td>
                <td class="td text-right">
                    @if ($u->device_hash)
                        <form method="POST" action="{{ route('admin.perangkat.reset', $u) }}" onsubmit="return confirm('Reset perangkat {{ $u->name }}? Dia akan logout dan perangkat berikutnya yang login jadi perangkat barunya.')">@csrf @method('DELETE')
                            <button class="btn btn-light">Reset</button></form>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endsection
