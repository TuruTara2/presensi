<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['name', 'jam_masuk', 'jam_pulang', 'toleransi_menit'];

    public function users() { return $this->hasMany(User::class); }

    /** Shift malam: jam pulang <= jam masuk (melewati tengah malam), mis. 20:00 -> 08:00. */
    public function isOvernight(): bool
    {
        return substr($this->jam_pulang, 0, 5) <= substr($this->jam_masuk, 0, 5);
    }
}
