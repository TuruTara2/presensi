@extends('layouts.app')
@section('title', 'Data Karyawan')
@section('content')
<div class="mb-4 flex flex-wrap items-end justify-between gap-4">
    <form class="flex flex-wrap items-end gap-2">
        <div><label class="label">Cari</label><input name="q" value="{{ request('q') }}" placeholder="Nama / NIP / email" class="input"></div>
        <div><label class="label">Divisi</label>
            <select name="division_id" class="input"><option value="">Semua</option>
                @foreach ($divisions as $d)<option value="{{ $d->id }}" @selected(request('division_id') == $d->id)>{{ $d->name }}</option>@endforeach
            </select></div>
        <button class="btn-light">Filter</button>
    </form>
    <a href="{{ route('admin.karyawan.create') }}" class="btn-primary">+ Tambah Karyawan</a>
</div>

<div class="card overflow-x-auto p-0">
    <table class="min-w-full divide-y">
        <thead class="bg-surface-low"><tr><th class="th">NIP</th><th class="th">Nama</th><th class="th">Divisi</th><th class="th">Shift</th><th class="th">Role</th><th class="th">Status</th><th class="th"></th></tr></thead>
        <tbody class="divide-y">
        @forelse ($users as $u)
            <tr>
                <td class="td">{{ $u->nip ?? '-' }}</td>
                <td class="td"><div class="font-medium">{{ $u->name }}</div><div class="text-sm text-slate-400">{{ $u->email }}</div></td>
                <td class="td">{{ $u->division->name ?? '-' }}</td>
                <td class="td">{{ $u->shift ? $u->shift->name.' ('.substr($u->shift->jam_masuk,0,5).')' : '-' }}</td>
                <td class="td">{{ ucfirst($u->role) }}</td>
                <td class="td">{!! $u->is_active ? '<span class="text-success">Aktif</span>' : '<span class="text-slate-400">Nonaktif</span>' !!}</td>
                <td class="td whitespace-nowrap text-right">
                    <a href="{{ route('admin.karyawan.edit', $u) }}" class="btn-light">Edit</a>
                    <form method="POST" action="{{ route('admin.karyawan.destroy', $u) }}" class="inline" onsubmit="return confirm('Hapus {{ $u->name }}? Data presensinya ikut terhapus.')">
                        @csrf @method('DELETE') <button class="btn-danger">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="td py-8 text-center text-slate-400">Tidak ada data.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
