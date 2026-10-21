<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Division;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    private const HARI = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    private const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public function index(Request $request)
    {
        $month = $this->month($request);
        $weeks = $this->weeks($month);
        $active = $this->activeWeek($request, $month, $weeks);
        $week = $weeks[$active - 1];

        $from = $week['from']->toDateString();
        $to = $week['to']->toDateString();

        $all = $this->query($request, $from, $to)->get();
        $byDate = $all->groupBy(fn ($r) => $r->date->toDateString());

        // Satu blok per hari (Senin-Minggu, dipotong di batas bulan), hari tanpa data tetap tampil
        $days = [];
        for ($d = $week['from']->copy(); $d <= $week['to']; $d->addDay()) {
            $days[] = [
                'label' => self::HARI[$d->dayOfWeekIso - 1].', '.$d->day.' '.self::BULAN[$d->month - 1].' '.$d->year,
                'isToday' => $d->isToday(),
                'rows' => $byDate->get($d->toDateString(), collect()),
            ];
        }

        return view('admin.laporan', [
            'days' => $days,
            'weeks' => $weeks,
            'active' => $active,
            'bulan' => $month->format('Y-m'),
            'bulanLabel' => self::BULAN[$month->month - 1].' '.$month->year,
            'sum' => $all->groupBy('status')->map->count(),
            'overtimeTotal' => (int) $all->sum('overtime_minutes'),
            'divisions' => Division::orderBy('name')->get(),
        ]);
    }

    public function export(Request $request)
    {
        [$from, $to] = $this->range($request);
        $rows = $this->query($request, $from, $to)->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM supaya Excel baca UTF-8
            fputcsv($out, ['Tanggal', 'NIP', 'Nama', 'Divisi', 'Jam Masuk', 'Jam Pulang', 'Status', 'Terlambat (menit)', 'Lembur (menit)', 'Foto Masuk', 'Foto Pulang']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->date->format('Y-m-d'), $r->user->nip, $r->user->name, $r->user->division?->name,
                    $r->check_in, $r->check_out, ucfirst($r->status), $r->late_minutes, $r->overtime_minutes,
                    $r->check_in_photo ? route('admin.laporan.foto', [$r, 'masuk']) : '',
                    $r->check_out_photo ? route('admin.laporan.foto', [$r, 'pulang']) : '',
                ]);
            }
            fclose($out);
        }, "laporan-presensi_{$from}_{$to}.csv", ['Content-Type' => 'text/csv']);
    }

    /** Tampilkan foto scan (disk privat) - hanya admin yang login. */
    public function foto(Attendance $attendance, string $type)
    {
        $path = $type === 'masuk' ? $attendance->check_in_photo : $attendance->check_out_photo;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return response()->file(Storage::disk('local')->path($path), [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    /** Bulan yang dipilih (format Y-m), default bulan ini. */
    private function month(Request $request): Carbon
    {
        $bulan = (string) $request->input('bulan');

        return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan)
            ? Carbon::parse($bulan.'-01')->startOfDay()
            : now()->startOfMonth();
    }

    /** Minggu dalam bulan: blok Senin-Minggu, minggu pertama/terakhir dipotong di batas bulan. */
    private function weeks(Carbon $month): array
    {
        $weeks = [];
        $end = $month->copy()->endOfMonth()->startOfDay();

        for ($cursor = $month->copy()->startOfMonth(); $cursor <= $end; $cursor = $cursor->copy()->addDay()) {
            $weekEnd = $cursor->copy()->addDays(7 - $cursor->dayOfWeekIso); // sampai hari Minggu
            if ($weekEnd > $end) {
                $weekEnd = $end->copy();
            }
            $weeks[] = [
                'no' => count($weeks) + 1,
                'from' => $cursor->copy(),
                'to' => $weekEnd->copy(),
                'range' => $cursor->day.'-'.$weekEnd->day.' '.mb_substr(self::BULAN[$weekEnd->month - 1], 0, 3),
            ];
            $cursor = $weekEnd->copy();
        }

        return $weeks;
    }

    /** Nomor minggu aktif: pilihan user, atau minggu yang memuat hari ini (kalau bulan ini), atau minggu 1. */
    private function activeWeek(Request $request, Carbon $month, array $weeks): int
    {
        if ($request->filled('minggu')) {
            return max(1, min(count($weeks), (int) $request->minggu));
        }
        if ($month->isSameMonth(now())) {
            foreach ($weeks as $w) {
                if (now()->between($w['from']->copy()->startOfDay(), $w['to']->copy()->endOfDay())) {
                    return $w['no'];
                }
            }
        }

        return 1;
    }

    /** Rentang tanggal untuk export: 1 bulan penuh, atau 1 minggu kalau ada parameter minggu. */
    private function range(Request $request): array
    {
        $month = $this->month($request);

        if ($request->filled('minggu')) {
            $weeks = $this->weeks($month);
            $w = $weeks[$this->activeWeek($request, $month, $weeks) - 1];

            return [$w['from']->toDateString(), $w['to']->toDateString()];
        }

        return [$month->copy()->startOfMonth()->toDateString(), $month->copy()->endOfMonth()->toDateString()];
    }

    private function query(Request $request, string $from, string $to)
    {
        return Attendance::with('user.division')
            ->whereBetween('date', [$from, $to])
            ->when($request->division_id, fn ($q, $d) => $q->whereHas('user', fn ($u) => $u->where('division_id', $d)))
            ->orderBy('date')->orderBy('check_in');
    }
}
