<?php

namespace App\Services;

use App\Models\User;

/**
 * QR dinamis berbasis waktu (time-based HMAC).
 * Payload baru : ABSEN|<user_id>|<slot_waktu>|<M atau P>|<hmac>   (M = QR Masuk, P = QR Pulang)
 * Payload lama : ABSEN|<user_id>|<slot_waktu>|<hmac>               (tanpa jenis, otomatis masuk/pulang)
 *
 * slot_waktu = floor(unix_time / 60). Halaman karyawan memperbarui QR otomatis,
 * dan server hanya menerima slot yang masih baru (sekitar 3-4 menit), jadi:
 *  - screenshot/QR lama tidak bisa dipakai lagi,
 *  - tidak bergantung pada pergantian tanggal (aman untuk shift malam).
 * Jenis QR ikut ditandatangani, jadi QR Masuk tidak bisa diubah menjadi QR Pulang.
 */
class QrToken
{
    public const STEP = 60;   // detik per slot
    public const BACK = 3;    // slot ke belakang yang masih diterima (~3-4 menit)

    /** @param string|null $type 'masuk' | 'pulang' | null (tanpa jenis) */
    public static function make(User $user, ?string $type = null): string
    {
        $slot = intdiv(time(), self::STEP);

        if ($type === null) {
            return "ABSEN|{$user->id}|{$slot}|".self::sign($user->id, $slot);
        }

        $t = $type === 'pulang' ? 'P' : 'M';

        return "ABSEN|{$user->id}|{$slot}|{$t}|".self::sign($user->id, $slot, $t);
    }

    /** @return array{user: User, type: ?string}|null */
    public static function parse(string $payload): ?array
    {
        $p = explode('|', trim($payload));
        $n = count($p);
        if (! in_array($n, [4, 5], true) || $p[0] !== 'ABSEN' || ! ctype_digit($p[1]) || ! ctype_digit($p[2])) {
            return null;
        }

        $t = null;
        if ($n === 5) {
            if (! in_array($p[3], ['M', 'P'], true)) {
                return null;
            }
            $t = $p[3];
        }
        [$id, $slot, $sig] = [$p[1], $p[2], $p[$n - 1]];

        $now = intdiv(time(), self::STEP);
        if ((int) $slot > $now + 1 || (int) $slot < $now - self::BACK) {
            return null; // kedaluwarsa
        }
        if (! hash_equals(self::sign($id, $slot, $t), $sig)) {
            return null; // tanda tangan salah
        }

        $user = User::find($id);

        return $user ? ['user' => $user, 'type' => $t === null ? null : ($t === 'P' ? 'pulang' : 'masuk')] : null;
    }

    public static function resolve(string $payload): ?User
    {
        return self::parse($payload)['user'] ?? null;
    }

    private static function sign($id, $slot, ?string $t = null): string
    {
        return hash_hmac('sha256', $t === null ? "{$id}|{$slot}" : "{$id}|{$slot}|{$t}", config('app.key'));
    }
}
