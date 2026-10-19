<?php

namespace App\Support;

class Duration
{
    /** 135 => "2 jam 15 menit", 45 => "45 menit", 0 => "0 menit" */
    public static function format(int|float|null $minutes): string
    {
        $m = (int) max(0, $minutes ?? 0);
        if ($m < 60) {
            return $m.' menit';
        }
        $h = intdiv($m, 60);
        $r = $m % 60;

        return $h.' jam'.($r ? " {$r} menit" : '');
    }
}
