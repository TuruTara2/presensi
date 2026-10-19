<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceRequest extends Model
{
    protected $fillable = ['user_id', 'device_hash', 'user_agent', 'ip', 'status', 'reviewed_by'];

    public function user() { return $this->belongsTo(User::class); }
}
