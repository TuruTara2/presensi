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

    public function process(Request $request)
    {
        $request->validate(['payload' => ['required', 'string', 'max:300']]);

        // QR ditandatangani HMAC + berlaku harian, jadi hanya QR asli karyawan yang diterima
        $user = QrToken::resolve($request->payload);
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'QR tidak valid atau sudah kedaluwarsa.'], 422);
        }

        $result = AttendanceService::scan($user);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
