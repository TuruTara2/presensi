<?php

namespace App\Http\Controllers;

use App\Services\NetworkGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NetworkPresenceController extends Controller
{
    public function heartbeat(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if(! $user || $user->isAdmin(), 403);

        $ip = $request->ip();
        if (! NetworkGuard::enabled() || ! NetworkGuard::allows($ip)) {
            return response()->json(['message' => 'Tidak terhubung ke jaringan kantor.'], 403);
        }

        $now = now();
        DB::table('network_presences')->upsert(
            [[
                'user_id' => $user->id,
                'ip_address' => $ip,
                'last_seen_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['user_id'],
            ['ip_address', 'last_seen_at', 'updated_at']
        );

        return response()->json(['ok' => true]);
    }
}
