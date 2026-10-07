@php
    $map = [
        'hadir' => 'bg-success/10 text-success-text', 'disetujui' => 'bg-success/10 text-success-text',
        'terlambat' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300', 'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300',
        'izin' => 'bg-slate-100 text-slate-700', 'cuti' => 'bg-blue-50 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300',
        'sakit' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/20 dark:text-sky-300', 'ditolak' => 'bg-error-container text-error-on',
    ];
@endphp
<span class="inline-block rounded px-2 py-1 text-sm font-semibold leading-none {{ $map[$s] ?? 'bg-slate-100 text-slate-600' }}">{{ ucfirst($s) }}</span>
