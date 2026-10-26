<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Shift;
use App\Models\User;
use App\Support\Duration;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AttendanceService
{
    public static function shiftFor(User $user): Shift
    {
        return $user->shift
            ?? Shift::first()
            ?? new Shift(['name' => 'Default', 'jam_masuk' => '08:00:00', 'jam_pulang' => '17:00:00', 'toleransi_menit' => 15]);
    }

    /**
     * Tanggal "milik" shift pada saat $now.
     * Shift malam (mis. 20:00 -> 08:00) dicatat pada tanggal MULAI shift. Scan jam 08:00 pagi
     * berarti pulang dari shift kemarin. Batas pergantian = titik tengah antara jam pulang & jam masuk
     * (20:00 & 08:00 -> 14:00), jadi scan sebelum jam 14:00 masih milik shift kemarin.
     */
    public static function shiftDate(User $user, ?Carbon $now = null): Carbon
    {
        $now = $now ?? now();
        $shift = self::shiftFor($user);

        if ($shift->isOvernight()) {
            $mid = (self::minutes($shift->jam_pulang) + self::minutes($shift->jam_masuk)) / 2;
            if ($now->hour * 60 + $now->minute < $mid) {
                return $now->copy()->subDay()->startOfDay();
            }
        }

        return $now->copy()->startOfDay();
    }

    /** [mulai, selesai] shift untuk tanggal shift tertentu (selesai bisa jatuh di hari berikutnya). */
    public static function window(Shift $shift, Carbon $date): array
    {
        $d = $date->toDateString();
        $start = Carbon::parse("{$d} {$shift->jam_masuk}");
        $end = Carbon::parse("{$d} {$shift->jam_pulang}");
        if ($end->lte($start)) {
            $end->addDay();
        }

        return [$start, $end];
    }

    /** Waktu lengkap absen masuk (menangani check-in setelah tengah malam pada shift malam). */
    public static function checkInAt(Attendance $att, Carbon $start): ?Carbon
    {
        if (! $att->check_in) {
            return null;
        }
        $dt = Carbon::parse($att->date->toDateString().' '.$att->check_in);
        if ($dt->lt($start->copy()->subHours(12))) {
            $dt->addDay();
        }

        return $dt;
    }

    /**
     * Hitung status, telat, dan lembur dari jam masuk/pulang yang diinput manual oleh admin,
     * memakai aturan shift & toleransi yang sama dengan scan QR.
     *
     * @return array{status: string, check_in: string, check_out: ?string, late_minutes: int, overtime_minutes: int}
     */
    public static function manualFields(User $user, Carbon $date, string $in, ?string $out): array
    {
        $shift = self::shiftFor($user);
        [$start, $end] = self::window($shift, $date);
        $limit = $start->copy()->addMinutes($shift->toleransi_menit);
        $d = $date->toDateString();

        $inAt = Carbon::parse("{$d} {$in}");
        if ($inAt->lt($start->copy()->subHours(12))) {
            $inAt->addDay(); // shift malam: masuk setelah tengah malam
        }

        $late = $inAt->gt($limit);
        $overtime = 0;
        if ($out) {
            $outAt = Carbon::parse("{$d} {$out}");
            while ($outAt->lt($inAt)) {
                $outAt->addDay();
            }
            $overtime = $outAt->gt($end) ? (int) floor(abs($end->diffInMinutes($outAt))) : 0;
        }

        return [
            'status' => $late ? 'terlambat' : 'hadir',
            'check_in' => $inAt->format('H:i:s'),
            'check_out' => $out ? Carbon::parse($out)->format('H:i:s') : null,
            'late_minutes' => $late ? (int) floor(abs($start->diffInMinutes($inAt))) : 0,
            'overtime_minutes' => $overtime,
        ];
    }

    /** Lembur sementara (menit) untuk karyawan yang sudah masuk tapi belum pulang. */
    public static function liveOvertime(User $user, ?Attendance $att): int
    {
        if (! $att || ! $att->check_in || $att->check_out) {
            return 0;
        }
        [, $end] = self::window(self::shiftFor($user), $att->date);

        return now()->gt($end) ? (int) floor(abs($end->diffInMinutes(now()))) : 0;
    }

    /**
     * Cek apakah jenis QR (masuk/pulang) cocok dengan kondisi absen hari ini.
     * Mengembalikan pesan error, atau null kalau cocok / QR tanpa jenis.
     */
    public static function precheck(User $user, ?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $date = self::shiftDate($user)->toDateString();
        $att = Attendance::where('user_id', $user->id)->whereDate('date', $date)->first();

        if ($type === 'masuk' && $att?->check_in) {
            return "{$user->name} sudah absen masuk hari ini. Gunakan QR Pulang untuk absen pulang.";
        }
        if ($type === 'pulang' && ! $att?->check_in) {
            return "{$user->name} belum absen masuk hari ini. Gunakan QR Masuk dulu.";
        }

        return null;
    }

    /** Proses hasil scan QR: absen masuk atau pulang. */
    public static function scan(User $user, ?UploadedFile $photo = null, ?string $type = null): array
    {
        if (! $user->is_active) {
            return ['ok' => false, 'message' => 'Akun karyawan nonaktif.'];
        }
        if ($msg = self::precheck($user, $type)) {
            return ['ok' => false, 'message' => $msg];
        }

        $now = now();
        $shift = self::shiftFor($user);
        $date = self::shiftDate($user, $now);
        $dateStr = $date->toDateString();
        [$start, $end] = self::window($shift, $date);

        $onLeave = LeaveRequest::where('user_id', $user->id)->where('status', 'disetujui')
            ->whereDate('start_date', '<=', $dateStr)->whereDate('end_date', '>=', $dateStr)->exists();
        if ($onLeave) {
            return ['ok' => false, 'message' => "{$user->name} sedang izin/sakit/cuti hari ini."];
        }

        $att = Attendance::firstOrNew(['user_id' => $user->id, 'date' => $dateStr]);

        // ---- Absen masuk
        if (! $att->check_in) {
            $limit = $start->copy()->addMinutes($shift->toleransi_menit);

            $att->check_in = $now->format('H:i:s');
            $att->overtime_minutes = 0;
            if ($now->gt($limit)) {
                $att->status = 'terlambat';
                $att->late_minutes = (int) floor(abs($start->diffInMinutes($now)));
            } else {
                $att->status = 'hadir';
                $att->late_minutes = 0;
            }
            $att->check_in_photo = self::storePhoto($photo, $user, 'masuk');
            $att->save();

            return [
                'ok' => true, 'type' => 'masuk', 'name' => $user->name,
                'time' => $now->format('H:i:s'), 'status' => $att->status,
                'message' => "Absen MASUK {$user->name} berhasil"
                    .($att->status === 'terlambat' ? ' (Terlambat '.Duration::format($att->late_minutes).').' : '.'),
            ];
        }

        // ---- Absen pulang
        if (! $att->check_out) {
            $inAt = self::checkInAt($att, $start);
            if ($inAt && abs($inAt->diffInMinutes($now)) < 1) {
                return ['ok' => false, 'message' => "{$user->name} baru saja absen masuk."];
            }

            $att->check_out = $now->format('H:i:s');
            $att->overtime_minutes = $now->gt($end) ? (int) floor(abs($end->diffInMinutes($now))) : 0;
            $att->check_out_photo = self::storePhoto($photo, $user, 'pulang');
            $att->save();

            return [
                'ok' => true, 'type' => 'pulang', 'name' => $user->name,
                'time' => $now->format('H:i:s'), 'status' => $att->status,
                'overtime_minutes' => $att->overtime_minutes,
                'overtime_text' => $att->overtime_minutes ? Duration::format($att->overtime_minutes) : null,
                'message' => "Absen PULANG {$user->name} berhasil"
                    .($att->overtime_minutes ? ' (Lembur '.Duration::format($att->overtime_minutes).').' : '.'),
            ];
        }

        return ['ok' => false, 'message' => "{$user->name} sudah absen masuk & pulang hari ini."];
    }

    /** Simpan foto scan di disk privat (bukan public), hanya bisa dilihat admin lewat route khusus. */
    private static function storePhoto(?UploadedFile $photo, User $user, string $type): ?string
    {
        if (! $photo) {
            return null;
        }

        $path = 'attendance/'.now()->format('Y-m').'/'.$user->id.'_'.now()->format('Ymd_His').'_'.$type.'.jpg';

        // Pakai getPathname(), bukan getRealPath()/storeAs(): realpath() bisa mengembalikan false di Windows
        // kalau folder temp upload tidak bisa ditelusuri, dan itu memicu error "Path cannot be empty".
        $tmp = $photo->getPathname();
        $stream = $tmp !== '' ? @fopen($tmp, 'rb') : false;
        if ($stream === false) {
            throw new \RuntimeException('Foto wajah tidak bisa dibaca dari folder temp upload PHP ('.($tmp ?: 'kosong').'). Cek upload_tmp_dir di php.ini.');
        }

        try {
            Storage::disk('local')->put($path, $stream);
        } finally {
            fclose($stream);
        }

        return $path;
    }

    private static function minutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }
}
