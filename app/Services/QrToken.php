<?php

namespace App\Services;

use App\Models\User;

/**
 * Payload QR: ABSEN|<user_id>|<tanggal>|<hmac>
 * Berubah tiap hari, jadi screenshot QR kemarin tidak bisa dipakai lagi.
 */
class QrToken
{
    public static function make(User $user): string
    {
        $date = today()->toDateString();

        return "ABSEN|{$user->id}|{$date}|".self::sign($user->id, $date);
    }

    public static function resolve(string $payload): ?User
    {
        $p = explode('|', trim($payload));
        if (count($p) !== 4 || $p[0] !== 'ABSEN') {
            return null;
        }
        [, $id, $date, $sig] = $p;

        if ($date !== today()->toDateString() || ! hash_equals(self::sign($id, $date), $sig)) {
            return null;
        }

        return User::find($id);
    }

    private static function sign($id, string $date): string
    {
        return hash_hmac('sha256', "{$id}|{$date}", config('app.key'));
    }
}
