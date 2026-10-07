<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id', 'type', 'start_date', 'end_date', 'reason',
        'attachment', 'status', 'admin_note', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function user() { return $this->belongsTo(User::class); }
}
