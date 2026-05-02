<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAttendanceUpdateRequest;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function list(Request $request)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $date = $request->query('date', now()->toDateString());
        $currentDate = Carbon::parse($date);

        $prevDate = $currentDate->copy()->subDay();
        $nextDate = $currentDate->copy()->addDay();

        $attendances = Attendance::with('breaks')
            ->whereDate('work_date', $currentDate->toDateString())
            ->get()
            ->keyBy('user_id');

        $users = User::where('role', User::ROLE_USER)
            ->orderBy('name')
            ->get();

        $rows = $users->map(function ($u) use ($attendances) {
            $a = $attendances->get($u->id);

            if (! $a) {
                return [
                    'attendance_id' => null,
                    'name' => $u->name,
                    'clock_in' => '',
                    'clock_out' => '',
                    'break_time' => '',
                    'work_time' => '',
                ];
            }

            $breakSeconds = 0;
            foreach ($a->breaks as $b) {
                if ($b->break_start_at && $b->break_end_at) {
                    $breakSeconds += Carbon::parse($b->break_end_at)
                        ->diffInSeconds(Carbon::parse($b->break_start_at));
                }
            }

            $workSeconds = 0;
            if ($a->clock_in_at && $a->clock_out_at) {
                $workSeconds = Carbon::parse($a->clock_out_at)
                        ->diffInSeconds(Carbon::parse($a->clock_in_at)) - $breakSeconds;
                if ($workSeconds < 0) $workSeconds = 0;
            }

            $breakTime = $breakSeconds
                ? sprintf('%d:%02d', floor($breakSeconds / 3600), ($breakSeconds % 3600) / 60)
                : '';

            $workTime = $workSeconds
                ? sprintf('%d:%02d', floor($workSeconds / 3600), ($workSeconds % 3600) / 60)
                : '';

            return [
                'attendance_id' => $a->id,
                'name' => $u->name,
                'clock_in' => $a->clock_in_at ? Carbon::parse($a->clock_in_at)->format('H:i') : '',
                'clock_out' => $a->clock_out_at ? Carbon::parse($a->clock_out_at)->format('H:i') : '',
                'break_time' => $breakTime,
                'work_time' => $workTime,
            ];
        });

        return view('admin.attendance_index', compact('rows', 'currentDate', 'prevDate', 'nextDate'));
    }

    public function show($id)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $attendance = Attendance::with([
                'user',
                'breaks' => fn ($q) => $q->orderBy('break_start_at')
            ])
            ->findOrFail($id);

        $break1 = $attendance->breaks->get(0);
        $break2 = $attendance->breaks->get(1);

        $userName = $attendance->user?->name ?? '';

        $clockIn  = $attendance->clock_in_at  ? Carbon::parse($attendance->clock_in_at)->format('H:i') : '';
        $clockOut = $attendance->clock_out_at ? Carbon::parse($attendance->clock_out_at)->format('H:i') : '';

        $break1Start = ($break1 && $break1->break_start_at) ? Carbon::parse($break1->break_start_at)->format('H:i') : '';
        $break1End   = ($break1 && $break1->break_end_at)   ? Carbon::parse($break1->break_end_at)->format('H:i') : '';

        $break2Start = ($break2 && $break2->break_start_at) ? Carbon::parse($break2->break_start_at)->format('H:i') : '';
        $break2End   = ($break2 && $break2->break_end_at)   ? Carbon::parse($break2->break_end_at)->format('H:i') : '';

        $d = Carbon::parse($attendance->work_date);

        return view('admin.attendance_show', compact(
            'attendance',
            'userName',
            'clockIn',
            'clockOut',
            'break1Start',
            'break1End',
            'break2Start',
            'break2End',
            'd'
        ));
    }

    public function update(AdminAttendanceUpdateRequest $request, $id)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $attendance = Attendance::with('breaks')->findOrFail($id);

        $date = $request->work_date;

        DB::transaction(function () use ($request, $attendance, $date) {

            $attendance->update([
                'work_date'    => $date,
                'clock_in_at'  => Carbon::parse("$date {$request->clock_in_at}"),
                'clock_out_at' => Carbon::parse("$date {$request->clock_out_at}"),
                'note'         => $request->note,
            ]);

            $attendance->breaks()->delete();

            foreach ($request->input('breaks', []) as $b) {
                $bs = $b['break_start_at'] ?? null;
                $be = $b['break_end_at'] ?? null;

                if (! $bs && ! $be) continue;

                $attendance->breaks()->create([
                    'break_start_at' => $bs ? Carbon::parse("$date $bs") : null,
                    'break_end_at'   => $be ? Carbon::parse("$date $be") : null,
                ]);
            }
        });

        return redirect()->route('admin.attendance.show', ['id' => $attendance->id]);
    }
}