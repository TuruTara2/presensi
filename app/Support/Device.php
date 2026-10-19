<?php

namespace App\Support;

class Device
{
    /** Label singkat dari user-agent, mis. "Android · Chrome". */
    public static function label(?string $ua): string
    {
        $ua = $ua ?? '';
        $os = match (true) {
            str_contains($ua, 'Android') => 'Android',
            preg_match('/iPhone|iPad|iPod/', $ua) === 1 => 'iOS',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Perangkat tidak dikenal',
        };
        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Browser',
        };

        return "{$os} · {$browser}";
    }
}
