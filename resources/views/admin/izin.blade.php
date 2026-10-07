@extends('layouts.app')
@section('title', 'Verifikasi & Persetujuan Izin')
@section('content')
<div class="mb-4 flex gap-2">
    @foreach (['pending' => 'Menunggu', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak', 'semua' => 'Semua'] as $k => $v)
        <a href="{{ route('admin.izin', ['status' => $k]) }}" class="{{ $status === $k ? 'btn-primary' : 'btn-light' }}">{{ $v }}</a>
    @endforeach
</div>
<div class="space-y-4">
@forelse ($items as $i)
    <div class="card">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <div >{{ $i->user->name }} <span class="text-sm font-normal text-slate-400">{{ $i->user->division->name ?? '' }}</span></div>
                <div class="mt-1 flex items-center gap-2 text-sm">
                    @include('partials.badge', ['s' => $i->type])
                    {{ $i->start_date->format('d M Y') }} - {{ $i->end_date->format('d M Y') }}
                </div>
                <p class="mt-2 text-sm text-slate-600">{{ $i->reason }}</p>
                @if ($i->attachment)
                    <a class="mt-1 inline-block text-sm text-primary underline" target="_blank" href="{{ Storage::url($i->attachment) }}">Lihat lampiran</a>
                @endif
            </div>
            <div>@include('partials.badge', ['s' => $i->status])</div>
        </div>
        @if ($i->status === 'pending')
            <form method="POST" action="{{ route('admin.izin.decide', $i) }}" class="mt-4 flex flex-wrap items-end gap-2 border-t pt-4">
                @csrf @method('PUT')
                <div class="flex-1"><label class="label">Catatan (opsional)</label><input name="admin_note" class="input"></div>
                <button name="status" value="disetujui" class="btn-primary">Setujui</button>
                <button name="status" value="ditolak" class="btn-danger">Tolak</button>
            </form>
        @elseif ($i->admin_note)
            <p class="mt-4 border-t pt-4 text-sm text-slate-500">Catatan admin: {{ $i->admin_note }}</p>
        @endif
    </div>
@empty
    <div class="card py-10 text-center text-slate-400">Tidak ada pengajuan.</div>
@endforelse
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
