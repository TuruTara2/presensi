<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Services\AttendanceService;
use App\Services\NetworkGuard;
use App\Services\QrToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class KaryawanController extends Controller
{
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $date = AttendanceService::shiftDate($user); // shift malam: tanggal mulai shift
        $today = Attendance::where('user_id', $user->id)->whereDate('date', $date)->first();
        $leave = LeaveRequest::where('user_id', $user->id)->where('status', 'disetujui')
            ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first();

        return view('karyawan.dashboard', [
            'today' => $today,
            'leave' => $leave,
            'overtimeLive' => AttendanceService::liveOvertime($user, $today),
            'shift' => AttendanceService::shiftFor($user),
            'qr' => NetworkGuard::allows($request->ip()) ? QrToken::make($user) : null,
        ]);
    }

    /** QR terbaru (dipanggil otomatis oleh halaman karyawan tiap 30 detik). */
    public function qr(Request $request)
    {
        // QR hanya diterbitkan kalau HP karyawan terhubung ke jaringan kantor
        if (! NetworkGuard::allows($request->ip())) {
            return response()->json(['ok' => false, 'message' => 'Tidak terhubung ke jaringan kantor.'], 403);
        }

        return response()->json(['ok' => true, 'payload' => QrToken::make($request->user())]);
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
        $user = $request->user()->load('division', 'shift');

        $rows = Attendance::where('user_id', $user->id)
            ->whereYear('date', now()->year)->whereMonth('date', now()->month)->get();
        $stats = [
            'hadir' => $rows->where('status', 'hadir')->count(),
            'terlambat' => $rows->where('status', 'terlambat')->count(),
            'izin' => $rows->whereIn('status', ['izin', 'sakit', 'cuti'])->count(),
            'lembur' => (int) $rows->sum('overtime_minutes'),
        ];

        return view('karyawan.profil', compact('user', 'stats'));
    }

    public function updateProfil(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        $user->fill(collect($data)->only(['name', 'email', 'phone'])->all());

        if ($request->hasFile('photo')) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $user->photo = $request->file('photo')->store('avatars', 'public');
        } elseif ($request->boolean('remove_photo') && $user->photo) {
            Storage::disk('public')->delete($user->photo);
            $user->photo = null;
        }

        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'min:6', 'confirmed'],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Password berhasil diganti.');
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
