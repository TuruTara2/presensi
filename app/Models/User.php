<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'nip', 'phone', 'photo', 'role',
        'division_id', 'shift_id', 'is_active', 'password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'device_bound_at' => 'datetime',
        ];
    }

    /** Email reset password pakai template Bahasa Indonesia. */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function division() { return $this->belongsTo(Division::class); }
    public function shift() { return $this->belongsTo(Shift::class); }
    public function attendances() { return $this->hasMany(Attendance::class); }
    public function leaveRequests() { return $this->hasMany(LeaveRequest::class); }
}
