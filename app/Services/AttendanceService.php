<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;

class AttendanceService
{
    public static function shiftFor(User $user): Shift
    {
        return $user->shift
            ?? Shift::first()
            ?? new Shift(['name' => 'Default', 'jam_masuk' => '08:00:00', 'jam_pulang' => '17:00:00', 'toleransi_menit' => 15]);
    }

    /** Proses hasil scan QR: absen masuk atau pulang. */
    public static function scan(User $user): array
    {
        if (! $user->is_active) {
            return ['ok' => false, 'message' => 'Akun karyawan nonaktif.'];
        }

        $now = now();
        $today = $now->toDateString();

        $onLeave = LeaveRequest::where('user_id', $user->id)->where('status', 'disetujui')
            ->whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->exists();
        if ($onLeave) {
            return ['ok' => false, 'message' => "{$user->name} sedang izin/sakit/cuti hari ini."];
        }

        $att = Attendance::firstOrNew(['user_id' => $user->id, 'date' => $today]);

        // ---- Absen masuk
        if (! $att->check_in) {
            $shift = self::shiftFor($user);
            $start = Carbon::parse("{$today} {$shift->jam_masuk}");
            $limit = $start->copy()->addMinutes($shift->toleransi_menit);

            $att->check_in = $now->format('H:i:s');
            if ($now->gt($limit)) {
                $att->status = 'terlambat';
                $att->late_minutes = (int) $start->diffInMinutes($now);
            } else {
                $att->status = 'hadir';
                $att->late_minutes = 0;
            }
            $att->save();

            return [
                'ok' => true, 'type' => 'masuk', 'name' => $user->name,
                'time' => $now->format('H:i:s'), 'status' => $att->status,
                'message' => "Absen MASUK {$user->name} berhasil".($att->status === 'terlambat' ? " (Terlambat {$att->late_minutes} menit)." : '.'),
            ];
        }

        // ---- Absen pulang
        if (! $att->check_out) {
            if (Carbon::parse("{$today} {$att->check_in}")->diffInMinutes($now) < 1) {
                return ['ok' => false, 'message' => "{$user->name} baru saja absen masuk."];
            }
            $att->check_out = $now->format('H:i:s');
            $att->save();

            return [
                'ok' => true, 'type' => 'pulang', 'name' => $user->name,
                'time' => $now->format('H:i:s'), 'status' => $att->status,
                'message' => "Absen PULANG {$user->name} berhasil.",
            ];
        }

        return ['ok' => false, 'message' => "{$user->name} sudah absen masuk & pulang hari ini."];
    }
}
