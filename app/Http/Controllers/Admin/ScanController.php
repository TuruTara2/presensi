<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\AttendanceService;
use App\Services\QrToken;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function index()
    {
        $recent = Attendance::with('user')->whereDate('date', today())
            ->latest('updated_at')->limit(10)->get();

        return view('admin.scan', compact('recent'));
    }

    public function process(Request $request)
    {
        $request->validate(['payload' => ['required', 'string', 'max:300']]);

        $user = QrToken::resolve($request->payload);
        if (! $user) {
            return response()->json(['ok' => false, 'message' => 'QR tidak valid atau sudah kedaluwarsa.'], 422);
        }

        $result = AttendanceService::scan($user);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
