<?php

namespace App\Http\Controllers;

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
