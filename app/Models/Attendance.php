<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['user_id', 'date', 'check_in', 'check_out', 'status', 'late_minutes', 'overtime_minutes'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function user() { return $this->belongsTo(User::class); }
}
