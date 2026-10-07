<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    protected $fillable = ['name', 'jam_masuk', 'jam_pulang', 'toleransi_menit'];

    public function users() { return $this->hasMany(User::class); }
}
