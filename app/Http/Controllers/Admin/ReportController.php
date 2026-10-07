<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Division;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        [$from, $to] = $this->range($request);
        $rows = $this->query($request, $from, $to)->paginate(20)->withQueryString();

        $sum = $this->query($request, $from, $to)->get()->groupBy('status')->map->count();

        return view('admin.laporan', [
            'rows' => $rows, 'from' => $from, 'to' => $to, 'sum' => $sum,
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
            fputcsv($out, ['Tanggal', 'NIP', 'Nama', 'Divisi', 'Jam Masuk', 'Jam Pulang', 'Status', 'Terlambat (menit)']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->date->format('Y-m-d'), $r->user->nip, $r->user->name, $r->user->division?->name,
                    $r->check_in, $r->check_out, ucfirst($r->status), $r->late_minutes,
                ]);
            }
            fclose($out);
        }, "laporan-presensi_{$from}_{$to}.csv", ['Content-Type' => 'text/csv']);
    }

    private function range(Request $request): array
    {
        if ($request->filled('bulan')) {
            $d = \Carbon\Carbon::parse($request->bulan.'-01');

            return [$d->startOfMonth()->toDateString(), $d->endOfMonth()->toDateString()];
        }

        return [
            $request->input('from', now()->startOfMonth()->toDateString()),
            $request->input('to', now()->toDateString()),
        ];
    }

    private function query(Request $request, string $from, string $to)
    {
        return Attendance::with('user.division')
            ->whereBetween('date', [$from, $to])
            ->when($request->division_id, fn ($q, $d) => $q->whereHas('user', fn ($u) => $u->where('division_id', $d)))
            ->orderByDesc('date')->orderBy('check_in');
    }
}
