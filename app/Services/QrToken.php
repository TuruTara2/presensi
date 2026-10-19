<?php

namespace App\Services;

use App\Models\User;

/**
 * QR dinamis berbasis waktu (time-based HMAC).
 * Payload: ABSEN|<user_id>|<slot_waktu>|<hmac>
 *
 * slot_waktu = floor(unix_time / 60). Halaman karyawan memperbarui QR otomatis,
 * dan server hanya menerima slot yang masih baru (sekitar 3-4 menit), jadi:
 *  - screenshot/QR lama tidak bisa dipakai lagi,
 *  - tidak bergantung pada pergantian tanggal (aman untuk shift malam).
 */
class QrToken
{
    public const STEP = 60;   // detik per slot
    public const BACK = 3;    // slot ke belakang yang masih diterima (~3-4 menit)

    public static function make(User $user): string
    {
        $slot = intdiv(time(), self::STEP);

        return "ABSEN|{$user->id}|{$slot}|".self::sign($user->id, $slot);
    }

    public static function resolve(string $payload): ?User
    {
        $p = explode('|', trim($payload));
        if (count($p) !== 4 || $p[0] !== 'ABSEN' || ! ctype_digit($p[1]) || ! ctype_digit($p[2])) {
            return null;
        }
        [, $id, $slot, $sig] = $p;

        $now = intdiv(time(), self::STEP);
        if ((int) $slot > $now + 1 || (int) $slot < $now - self::BACK) {
            return null; // kedaluwarsa
        }
        if (! hash_equals(self::sign($id, $slot), $sig)) {
            return null; // tanda tangan salah
        }

        return User::find($id);
    }

    private static function sign($id, $slot): string
    {
        return hash_hmac('sha256', "{$id}|{$slot}", config('app.key'));
    }
}
