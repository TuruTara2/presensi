<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\DeviceRequest;
use App\Models\LeaveRequest;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $totalEmp = User::where('role', 'karyawan')->where('is_active', true)->count();
        $todayRows = Attendance::with('user.division')->whereDate('date', today())->orderBy('check_in')->get();

        $onTime = $todayRows->where('status', 'hadir');
        $late = $todayRows->where('status', 'terlambat');
        $leave = $todayRows->whereIn('status', ['izin', 'sakit', 'cuti'])->count();
        $absent = max(0, $totalEmp - $todayRows->count());

        $days = collect(range(6, 0))->map(fn ($i) => today()->subDays($i));
        $week = Attendance::whereBetween('date', [$days->first()->toDateString(), today()->toDateString()])
            ->get()->groupBy(fn ($a) => $a->date->toDateString());

        $chartWeek = [
            'labels' => $days->map(fn ($d) => $d->format('d/m'))->values(),
            'hadir' => $days->map(fn ($d) => ($week[$d->toDateString()] ?? collect())->where('status', 'hadir')->count())->values(),
            'terlambat' => $days->map(fn ($d) => ($week[$d->toDateString()] ?? collect())->where('status', 'terlambat')->count())->values(),
        ];

        return view('admin.dashboard', [
            'totalEmp' => $totalEmp,
            'onTime' => $onTime,
            'late' => $late,
            'leave' => $leave,
            'absent' => $absent,
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'pendingDevices' => DeviceRequest::where('status', 'pending')->count(),
            'chartWeek' => $chartWeek,
        ]);
    }
}
