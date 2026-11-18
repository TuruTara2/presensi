<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Services\QrToken;
use Illuminate\Http\Request;

/** Halaman scan QR publik (kiosk) - bisa dibuka tanpa login. */
class ScanController extends Controller
{
    public function index()
    {
        return view('scan');
    }

    public function recent()
    {
        $attendances = Attendance::with('user:id,name')
            ->whereDate('date', today())
            ->latest('updated_at')
            ->latest('id')
            ->limit(30)
            ->get(['id', 'user_id', 'check_in', 'check_out', 'status', 'is_manual']);

        return response()->json($attendances->map(fn (Attendance $attendance) => [
            'name' => $attendance->user->name,
            'check_in' => $attendance->check_in ? substr($attendance->check_in, 0, 5) : null,
            'check_out' => $attendance->check_out ? substr($attendance->check_out, 0, 5) : null,
            'status' => $attendance->status,
            'is_manual' => $attendance->is_manual,
        ]));
    }

    /** Tahap 1: cek QR saja (belum mencatat absen), supaya kiosk bisa lanjut ke tahap foto wajah. */
    public function verify(Request $request)
    {
        $request->validate(['payload' => ['required', 'string', 'max:300']]);

        $qr = QrToken::parse($request->payload);
        if (! $qr) {
            return response()->json(['ok' => false, 'message' => 'QR tidak valid atau sudah kedaluwarsa.'], 422);
        }
        $user = $qr['user'];
        if (! $user->is_active) {
            return response()->json(['ok' => false, 'message' => 'Akun karyawan nonaktif.'], 422);
        }
        // QR Masuk/Pulang tidak sesuai kondisi hari ini: tolak sebelum tahap foto wajah
        if ($msg = AttendanceService::precheck($user, $qr['type'])) {
            return response()->json(['ok' => false, 'message' => $msg], 422);
        }

        return response()->json(['ok' => true, 'name' => $user->name, 'type' => $qr['type']]);
    }

    /** Tahap 2: QR + foto wajah -> absen dicatat. */
    public function process(Request $request)
    {
        $request->validate([
            'payload' => ['required', 'string', 'max:300'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg', 'max:1024'], // foto wajah wajib
        ], [
            'photo.required' => 'Foto wajah wajib diambil.',
        ]);

        // QR ditandatangani HMAC + berlaku harian, jadi hanya QR asli karyawan yang diterima
        $qr = QrToken::parse($request->payload);
        if (! $qr) {
            return response()->json(['ok' => false, 'message' => 'QR tidak valid atau sudah kedaluwarsa.'], 422);
        }

        $result = AttendanceService::scan($qr['user'], $request->file('photo'), $qr['type']);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
