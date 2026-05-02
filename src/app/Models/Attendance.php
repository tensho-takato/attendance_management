<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\AttendanceBreak;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'note',
    ];

    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
