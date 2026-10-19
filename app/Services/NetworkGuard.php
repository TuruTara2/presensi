<?php

namespace App\Services;

use App\Models\OfficeNetwork;

/**
 * Validasi IP jaringan kantor.
 * Dipakai saat QR DITERBITKAN (di HP karyawan), bukan saat scan di kantor,
 * karena yang perlu dibuktikan adalah "HP karyawan sedang di jaringan kantor".
 * Jika belum ada jaringan aktif, validasi dianggap nonaktif (semua IP diizinkan).
 */
class NetworkGuard
{
    public static function enabled(): bool
    {
        return OfficeNetwork::where('is_active', true)->exists();
    }

    public static function allows(?string $ip): bool
    {
        $networks = OfficeNetwork::where('is_active', true)->pluck('cidr');
        if ($networks->isEmpty()) {
            return true;
        }
        if (! $ip) {
            return false;
        }
        foreach ($networks as $cidr) {
            if (self::matches($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    /** Cocokkan IP dengan IP tunggal atau CIDR (IPv4 & IPv6). */
    public static function matches(string $ip, string $cidr): bool
    {
        $cidr = trim($cidr);
        $ipBin = @inet_pton($ip);
        if ($ipBin === false) {
            return false;
        }

        if (! str_contains($cidr, '/')) {
            return $ipBin === @inet_pton($cidr);
        }

        [$subnet, $bits] = explode('/', $cidr, 2);
        $subBin = @inet_pton($subnet);
        $bits = (int) $bits;
        if ($subBin === false || strlen($subBin) !== strlen($ipBin) || $bits < 0 || $bits > strlen($ipBin) * 8) {
            return false;
        }

        $bytes = intdiv($bits, 8);
        $rest = $bits % 8;
        if ($bytes && substr($ipBin, 0, $bytes) !== substr($subBin, 0, $bytes)) {
            return false;
        }
        if ($rest) {
            $mask = (0xFF << (8 - $rest)) & 0xFF;
            if ((ord($ipBin[$bytes]) & $mask) !== (ord($subBin[$bytes]) & $mask)) {
                return false;
            }
        }

        return true;
    }
}
