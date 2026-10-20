<h2 class="mb-4 font-semibold">Status Hari Ini</h2>
@if ($leave)
    <div class="mb-2">@include('partials.badge', ['s' => $leave->type])</div>
    <p class="text-sm text-slate-600">Kamu sedang {{ $leave->type }} sampai {{ $leave->end_date->format('d M Y') }}.</p>
@elseif ($today && $today->check_in)
    <div class="mb-4">@include('partials.badge', ['s' => $today->status])
        @if ($today->late_minutes) <span class="ml-2 text-sm text-slate-500">(terlambat {{ \App\Support\Duration::format($today->late_minutes) }})</span> @endif
    </div>
    <dl class="grid grid-cols-2 gap-4 text-sm">
        <div class="rounded-xl bg-slate-50 p-4"><dt class="text-slate-500">Jam Masuk</dt><dd class="text-lg font-semibold">{{ substr($today->check_in,0,5) }}</dd></div>
        <div class="rounded-xl bg-slate-50 p-4"><dt class="text-slate-500">Jam Keluar</dt><dd class="text-lg font-semibold">{{ $today->check_out ? substr($today->check_out,0,5).($today->check_out < $today->check_in ? ' (+1)' : '') : '-' }}</dd></div>
    </dl>
    @if ($today->check_out && $today->overtime_minutes)
        <p class="mt-4 rounded-xl bg-amber-50 px-4 py-4 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">Lembur: <b>{{ \App\Support\Duration::format($today->overtime_minutes) }}</b></p>
    @elseif (! $today->check_out && $overtimeLive)
        <p class="mt-4 rounded-xl bg-amber-50 px-4 py-4 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">Kamu sudah melewati jam pulang ({{ substr($shift->jam_pulang,0,5) }}). Lembur sementara: <b>{{ \App\Support\Duration::format($overtimeLive) }}</b></p>
    @endif
@else
    <p class="text-sm text-slate-600">Kamu belum absen hari ini. Batas masuk: <b>{{ substr($shift->jam_masuk,0,5) }}</b> + toleransi {{ $shift->toleransi_menit }} menit.</p>
@endif
