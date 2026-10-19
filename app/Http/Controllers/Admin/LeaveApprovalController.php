<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class LeaveApprovalController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->input('status', 'pending');
        $items = LeaveRequest::with('user.division')
            ->when($status !== 'semua', fn ($q) => $q->where('status', $status))
            ->latest()->paginate(10)->withQueryString();

        return view('admin.izin', compact('items', 'status'));
    }

    public function decide(Request $request, LeaveRequest $izin)
    {
        $data = $request->validate([
            'status' => ['required', 'in:disetujui,ditolak'],
            'admin_note' => ['nullable', 'string', 'max:300'],
        ]);

        $izin->update($data + ['reviewed_by' => $request->user()->id]);

        // Kalau disetujui, tandai kehadiran pada rentang tanggal tsb (Senin-Jumat)
        if ($data['status'] === 'disetujui') {
            foreach (CarbonPeriod::create($izin->start_date, $izin->end_date) as $day) {
                if ($day->isWeekend()) {
                    continue;
                }
                Attendance::updateOrCreate(
                    ['user_id' => $izin->user_id, 'date' => $day->toDateString()],
                    ['status' => $izin->type, 'check_in' => null, 'check_out' => null, 'late_minutes' => 0, 'overtime_minutes' => 0]
                );
            }
        }

        return back()->with('success', 'Pengajuan '.$data['status'].'.');
    }
}
