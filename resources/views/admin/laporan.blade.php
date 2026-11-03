@extends('layouts.app')
@section('title', 'Rekapitulasi & Export Laporan')
@section('content')
{{-- Filter: bulan & divisi langsung diterapkan saat diganti --}}
<form method="GET" class="card mb-6 flex flex-wrap items-end gap-4">
    <div><label class="label">Bulan</label><input type="month" name="bulan" value="{{ $bulan }}" class="input" onchange="this.form.submit()"></div>
    <div><label class="label">Divisi</label>
        <select name="division_id" class="input" onchange="this.form.submit()"><option value="">Semua</option>
            @foreach ($divisions as $d)<option value="{{ $d->id }}" @selected(request('division_id') == $d->id)>{{ $d->name }}</option>@endforeach
        </select></div>
    <div class="ml-auto flex flex-wrap gap-2">
        <a href="{{ route('admin.laporan.export', array_merge(request()->only('bulan', 'division_id'), ['bulan' => $bulan, 'minggu' => $active])) }}" class="btn-light">Export Minggu {{ $active }} (CSV)</a>
        <a href="{{ route('admin.laporan.export', array_merge(request()->only('division_id'), ['bulan' => $bulan])) }}" class="btn-light">Export 1 Bulan (CSV)</a>
    </div>
</form>

{{-- Pilihan minggu --}}
<div class="mb-2 text-sm text-slate-500">{{ $bulanLabel }}</div>
<div class="mb-6 flex flex-wrap gap-2">
    @foreach ($weeks as $w)
        <a href="{{ route('admin.laporan', array_merge(request()->only('division_id'), ['bulan' => $bulan, 'minggu' => $w['no']])) }}"
           class="{{ $w['no'] === $active ? 'btn-primary' : 'btn-light' }}">
            Minggu {{ $w['no'] }} <span class="ml-1 text-xs opacity-80">({{ $w['range'] }})</span>
        </a>
    @endforeach
</div>

{{-- Ringkasan minggu terpilih --}}
<div class="mb-6 flex flex-wrap gap-2 text-sm">
    @foreach (['hadir', 'terlambat', 'izin', 'sakit', 'cuti'] as $st)
        <span class="rounded-xl bg-card px-4 py-2 ring-1 ring-slate-200">{{ ucfirst($st) }}: <b>{{ $sum[$st] ?? 0 }}</b></span>
    @endforeach
    <span class="rounded-xl bg-card px-4 py-2 ring-1 ring-slate-200">Total lembur: <b>{{ \App\Support\Duration::format($overtimeTotal) }}</b></span>
</div>

{{-- Satu blok per hari --}}
@foreach ($days as $day)
    <div class="card mb-6 overflow-hidden p-0">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b bg-surface-low px-6 py-4">
            <h3 class="font-semibold">
                {{ $day['label'] }}
                @if ($day['isToday'])<span class="ml-2 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">Hari ini</span>@endif
            </h3>
            <span class="text-sm text-slate-500">{{ $day['rows']->count() }} presensi</span>
        </div>

        @if ($day['rows']->isEmpty())
            <div class="px-6 py-5 text-sm text-slate-400">{{ $day['isFuture'] ? 'Belum berlangsung.' : 'Tidak ada data presensi.' }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y">
                    <thead><tr><th class="th">Nama</th><th class="th">Divisi</th><th class="th">Masuk</th><th class="th">Pulang</th><th class="th">Status</th><th class="th">Telat</th><th class="th">Lembur</th><th class="th">Verifikasi Wajah</th></tr></thead>
                    <tbody class="divide-y">
                    @foreach ($day['rows'] as $r)
                        <tr>
                            <td class="td">{{ $r->user->name }}</td>
                            <td class="td">{{ $r->user->division->name ?? '-' }}</td>
                            <td class="td">{{ $r->check_in ? substr($r->check_in,0,5) : '-' }}</td>
                            <td class="td">{{ $r->check_out ? substr($r->check_out,0,5) : '-' }}</td>
                            <td class="td">@include('partials.badge', ['s' => $r->status])@if ($r->is_manual)<span class="ml-1 text-xs text-slate-400" title="{{ $r->note }}">manual</span>@endif</td>
                            <td class="td">{{ $r->late_minutes ? \App\Support\Duration::format($r->late_minutes) : '-' }}</td>
                            <td class="td">{{ $r->overtime_minutes ? \App\Support\Duration::format($r->overtime_minutes) : '-' }}</td>
                            <td class="td">
                                <div class="flex items-start gap-2">
                                    @if ($r->check_in_photo || $r->check_out_photo)
                                        <div class="text-center text-xs text-slate-500" title="Foto profil akun (pembanding)">
                                            @include('partials.avatar', ['user' => $r->user, 'size' => 48])
                                            Akun
                                        </div>
                                        <span class="self-center text-slate-300">→</span>
                                    @endif
                                    @foreach (['masuk' => $r->check_in_photo, 'pulang' => $r->check_out_photo] as $tipe => $foto)
                                        @if ($foto)
                                            <button type="button"
                                                data-photo-preview="{{ route('admin.laporan.foto', [$r, $tipe]) }}"
                                                data-photo-alt="Foto {{ $tipe }} {{ $r->user->name }}"
                                                title="Lihat foto {{ $tipe }}"
                                                class="block cursor-zoom-in text-center text-xs text-slate-500">
                                                <img src="{{ route('admin.laporan.foto', [$r, $tipe]) }}" loading="lazy" alt="Foto {{ $tipe }} {{ $r->user->name }}" class="h-12 w-12 rounded-lg object-cover ring-1 ring-slate-200 transition hover:scale-105 hover:ring-primary">
                                                {{ ucfirst($tipe) }}
                                            </button>
                                        @endif
                                    @endforeach
                                    @if (! $r->check_in_photo && ! $r->check_out_photo)<span class="text-slate-400">-</span>@endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endforeach

{{-- Preview foto tanpa meninggalkan halaman laporan --}}
<div id="photoPreviewModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/80 p-4" role="dialog" aria-modal="true" aria-label="Pratinjau foto" aria-hidden="true">
    <button type="button" id="photoPreviewClose" class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/15 text-2xl text-white transition hover:bg-white/30" aria-label="Tutup">&times;</button>
    <img id="photoPreviewImage" src="" alt="Pratinjau foto absensi" class="max-h-[88vh] max-w-full rounded-xl object-contain shadow-2xl">
</div>
@endsection

@push('scripts')
<script>
    (() => {
        const modal = document.getElementById('photoPreviewModal');
        const preview = document.getElementById('photoPreviewImage');
        const closeButton = document.getElementById('photoPreviewClose');
        if (!modal || !preview || !closeButton) return;

        function closePreview() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');
            preview.src = '';
            document.body.classList.remove('overflow-hidden');
        }

        document.querySelectorAll('[data-photo-preview]').forEach(button => {
            button.addEventListener('click', () => {
                preview.src = button.dataset.photoPreview;
                preview.alt = button.dataset.photoAlt || 'Foto absensi';
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
                closeButton.focus();
            });
        });

        closeButton.addEventListener('click', closePreview);
        modal.addEventListener('click', event => {
            if (event.target === modal) closePreview();
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) closePreview();
        });
    })();
</script>
@endpush
