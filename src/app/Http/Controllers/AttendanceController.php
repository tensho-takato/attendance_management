<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Attendance;
use Illuminate\Support\Facades\DB;
use App\Models\StampCorrectionRequest;

class AttendanceController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $attendance = Attendance::with(['breaks' => function ($q) {
                $q->latest('id');
            }])
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        $dateLabel = now()->isoFormat('YYYY年M月D日(ddd)');
        $timeLabel = now()->format('H:i');

        if (! $attendance) {
            $status = 'off';
        } elseif ($attendance->clock_out_at) {
            $status = 'finished';
        } else {
            $latestBreak = $attendance->breaks->first();
            $status = ($latestBreak && $latestBreak->break_start_at && ! $latestBreak->break_end_at)
                ? 'break'
                : 'working';
        }

        return view('user.attendance_create', compact('status', 'dateLabel', 'timeLabel'));
    }

    public function clockIn()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        DB::transaction(function () use ($user, $today) {
            $attendance = Attendance::where('user_id', $user->id)
                ->where('work_date', $today)
                ->lockForUpdate()
                ->first();

            if (! $attendance) {
                Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $today,
                    'clock_in_at' => now(),
                ]);
                return;
            }

            if ($attendance->clock_in_at) {
                return;
            }

            $attendance->update([
                'clock_in_at' => now(),
            ]);
        });

        return redirect()->route('attendance.index');
    }

    public function breakIn()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $attendance = Attendance::with(['breaks' => function ($q) {
                $q->latest('id');
            }])
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        if (! $attendance || $attendance->clock_out_at) {
            return redirect()->route('attendance.index');
        }

        $latestBreak = $attendance->breaks->first();
        if ($latestBreak && $latestBreak->break_start_at && ! $latestBreak->break_end_at) {
            return redirect()->route('attendance.index');
        }

        DB::transaction(function () use ($attendance) {
            $attendance->breaks()->create([
                'break_start_at' => now(),
                'break_end_at'   => null,
            ]);
        });

        return redirect()->route('attendance.index');
    }

    public function breakOut()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $attendance = Attendance::with(['breaks' => function ($q) {
                $q->latest('id');
            }])
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        if (! $attendance || $attendance->clock_out_at) {
            return redirect()->route('attendance.index');
        }

        $latestBreak = $attendance->breaks->first();
        if (! $latestBreak || ! $latestBreak->break_start_at || $latestBreak->break_end_at) {
            return redirect()->route('attendance.index');
        }

        DB::transaction(function () use ($latestBreak) {
            $latestBreak->update([
                'break_end_at' => now(),
            ]);
        });

        return redirect()->route('attendance.index');
    }

    public function clockOut()
    {
        $user = auth()->user();
        $today = now()->toDateString();

        $attendance = Attendance::with(['breaks' => fn($q) => $q->latest('id')])
            ->where('user_id', $user->id)
            ->where('work_date', $today)
            ->first();

        if (! $attendance || $attendance->clock_out_at) {
            return redirect()->route('attendance.index');
        }

        $latestBreak = $attendance->breaks->first();
        if ($latestBreak && $latestBreak->break_start_at && ! $latestBreak->break_end_at) {
            return redirect()->route('attendance.index');
        }

        DB::transaction(function () use ($attendance) {
            $attendance->update([
                'clock_out_at' => now(),
            ]);
        });

        return redirect()->route('attendance.index');
    }

    public function list(Request $request)
    {
        $user = auth()->user();

        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy('work_date');

        $dates = [];
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
        $dates[] = $d->copy();
        }

        return view('user.attendance_index', compact('attendances', 'month', 'dates'));
    }

    public function detail($id)
    {
        $user = auth()->user();

        $attendance = Attendance::with(['breaks' => fn($q) => $q->orderBy('break_start_at')])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $break1 = $attendance->breaks->get(0);
        $break2 = $attendance->breaks->get(1);

        $clockIn  = $attendance->clock_in_at  ? Carbon::parse($attendance->clock_in_at)->format('H:i') : '';
        $clockOut = $attendance->clock_out_at ? Carbon::parse($attendance->clock_out_at)->format('H:i') : '';

        $break1Start = ($break1 && $break1->break_start_at) ? Carbon::parse($break1->break_start_at)->format('H:i') : '';
        $break1End   = ($break1 && $break1->break_end_at)   ? Carbon::parse($break1->break_end_at)->format('H:i') : '';

        $break2Start = ($break2 && $break2->break_start_at) ? Carbon::parse($break2->break_start_at)->format('H:i') : '';
        $break2End   = ($break2 && $break2->break_end_at)   ? Carbon::parse($break2->break_end_at)->format('H:i') : '';

        $hasPendingRequest = StampCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 0)
            ->exists();

        $userName = $user->name;

        $workDate = \Carbon\Carbon::parse($attendance->work_date);

        $workYear = $workDate->format('Y') . '年';
        $workMonthDay = $workDate->format('n月j日');

        return view('user.attendance_show', compact(
            'attendance',
            'userName',
            'clockIn',
            'clockOut',
            'break1Start',
            'break1End',
            'break2Start',
            'break2End',
            'hasPendingRequest',
            'workYear',
            'workMonthDay'
        ));
    }
}