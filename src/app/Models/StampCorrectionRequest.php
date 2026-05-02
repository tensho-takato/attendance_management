<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    protected $fillable = [
        'attendance_id',
        'user_id',
        'requested_work_date',
        'status',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'note',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'requested_clock_in_at' => 'datetime',
        'requested_clock_out_at' => 'datetime',
        'approved_at' => 'datetime',
        'requested_work_date' => 'date',
    ];

    public function breaks()
    {
        return $this->hasMany(StampCorrectionRequestBreak::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}