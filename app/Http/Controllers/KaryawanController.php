<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use App\Services\QrToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class KaryawanController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $today = Attendance::where('user_id', $user->id)->whereDate('date', today())->first();
        $leave = LeaveRequest::where('user_id', $user->id)->where('status', 'disetujui')
            ->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->first();

        return view('karyawan.dashboard', [
            'today' => $today,
            'leave' => $leave,
            'shift' => AttendanceService::shiftFor($user),
            'qr' => QrToken::make($user),
        ]);
    }

    public function riwayat(Request $request)
    {
        $month = $request->input('bulan', now()->format('Y-m'));
        [$y, $m] = explode('-', $month);

        $rows = Attendance::where('user_id', $request->user()->id)
            ->whereYear('date', $y)->whereMonth('date', $m)
            ->orderByDesc('date')->get();

        return view('karyawan.riwayat', compact('rows', 'month'));
    }

    public function profil(Request $request)
    {
        return view('karyawan.profil', ['user' => $request->user()->load('division', 'shift')]);
    }

    public function updateProfil(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'min:6', 'confirmed'],
        ]);

        $user->fill(collect($data)->only(['name', 'email', 'phone'])->all());
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function izinIndex(Request $request)
    {
        $items = LeaveRequest::where('user_id', $request->user()->id)->latest()->get();

        return view('karyawan.izin', compact('items'));
    }

    public function izinStore(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['izin', 'sakit', 'cuti'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        if ($request->hasFile('attachment')) {
            $data['attachment'] = $request->file('attachment')->store('lampiran', 'public');
        }
        $data['user_id'] = $request->user()->id;
        LeaveRequest::create($data);

        return redirect()->route('karyawan.izin')->with('success', 'Pengajuan terkirim, menunggu persetujuan admin.');
    }
}
