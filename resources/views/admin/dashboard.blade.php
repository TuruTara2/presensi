@extends('layouts.app')
@section('title', 'Dashboard Analisis')
@section('content')
<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-5">
    @foreach ([
        ['Total Karyawan', $totalEmp, 'text-slate-800'],
        ['Tepat Waktu', $onTime->count(), 'text-success'],
        ['Terlambat', $late->count(), 'text-amber-600'],
        ['Izin/Sakit/Cuti', $leave, 'text-slate-600'],
        ['Belum Hadir', $absent, 'text-primary'],
    ] as [$label, $val, $color])
        <div class="card"><div class="text-sm text-slate-500">{{ $label }}</div><div class="font-heading text-3xl font-semibold {{ $color }}">{{ $val }}</div></div>
    @endforeach
</div>

@if ($pending)
    <a href="{{ route('admin.izin') }}" class="mb-6 block rounded-xl bg-amber-50 px-4 py-4 text-sm text-amber-800 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/30">
        Ada <b>{{ $pending }}</b> pengajuan izin/sakit/cuti menunggu persetujuan &rarr;
    </a>
@endif

<div class="mb-6 grid gap-6 lg:grid-cols-3">
    <div class="card"><h2 class="mb-4 font-semibold">Statistik Hari Ini</h2><canvas id="chartToday" height="220"></canvas></div>
    <div class="card lg:col-span-2"><h2 class="mb-4 font-semibold">Kehadiran 7 Hari Terakhir</h2><canvas id="chartWeek" height="110"></canvas></div>
</div>

<div class="grid gap-6 lg:grid-cols-2">
    @foreach ([['Hadir Tepat Waktu', $onTime], ['Terlambat', $late]] as [$title, $list])
        <div class="card">
            <h2 class="mb-4 font-semibold">{{ $title }} Hari Ini ({{ $list->count() }})</h2>
            <ul class="divide-y text-sm">
                @forelse ($list as $a)
                    <li class="flex items-center justify-between py-2">
                        <span>{{ $a->user->name }} <span class="text-sm text-slate-400">{{ $a->user->division->name ?? '' }}</span></span>
                        <span class="text-slate-500">{{ substr($a->check_in, 0, 5) }}@if($a->late_minutes) &middot; +{{ $a->late_minutes }}m @endif</span>
                    </li>
                @empty
                    <li class="py-4 text-center text-slate-400">Belum ada data.</li>
                @endforelse
            </ul>
        </div>
    @endforeach
</div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    const isDark = document.documentElement.classList.contains('dark');
    Chart.defaults.color = isDark ? '#BEBEBE' : '#4A4A4A';
    Chart.defaults.borderColor = isDark ? '#343434' : '#EAEAEA';
    new Chart(document.getElementById('chartToday'), {
        type: 'doughnut',
        data: {
            labels: ['Tepat Waktu', 'Terlambat', 'Izin/Sakit/Cuti', 'Belum Hadir'],
            datasets: [{ data: [{{ $onTime->count() }}, {{ $late->count() }}, {{ $leave }}, {{ $absent }}],
                backgroundColor: ['#28A745', '#f59e0b', '#8A8A8A', '#DC143C'] }]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
    const w = @json($chartWeek);
    new Chart(document.getElementById('chartWeek'), {
        type: 'bar',
        data: { labels: w.labels, datasets: [
            { label: 'Tepat Waktu', data: w.hadir, backgroundColor: '#28A745' },
            { label: 'Terlambat', data: w.terlambat, backgroundColor: '#f59e0b' },
        ]},
        options: { scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } } }
    });
</script>
@endpush
