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

        $user = QrToken::resolve($request->payload);
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'QR tidak valid atau sudah kedaluwarsa.'], 422);
        }
        if (! $user->is_active) {
            return response()->json(['ok' => false, 'message' => 'Akun karyawan nonaktif.'], 422);
        }

        return response()->json(['ok' => true, 'name' => $user->name]);
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
        $user = QrToken::resolve($request->payload);
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'QR tidak valid atau sudah kedaluwarsa.'], 422);
        }

        $result = AttendanceService::scan($user, $request->file('photo'));

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
