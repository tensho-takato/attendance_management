<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $users = User::where('role', User::ROLE_USER)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.staff_index', compact('users'));
    }

    public function monthly(Request $request, User $user)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        abort_unless($user->role === User::ROLE_USER, 404);

        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

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

        $prevMonth = $start->copy()->subMonth()->format('Y-m');
        $nextMonth = $start->copy()->addMonth()->format('Y-m');

        return view('admin.staff_attendance_index', compact(
            'user',
            'month',
            'start',
            'end',
            'attendances',
            'dates',
            'prevMonth',
            'nextMonth'
        ));
    }

    public function csv(Request $request, User $user)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
        abort_unless($user->role === User::ROLE_USER, 404);

        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy('work_date');

        $filename = sprintf('%s_%s.csv', $user->name, $start->format('Y-m'));

        return response()->streamDownload(function () use ($start, $end, $attendances) {
            $out = fopen('php://output', 'w');

            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['日付', '出勤', '退勤', '休憩', '合計']);

            $week = ['日','月','火','水','木','金','土'];

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $key = $d->toDateString();
                $a = $attendances->get($key);

                if (! $a) {
                    fputcsv($out, [
                        $d->format('m/d') . '(' . $week[$d->dayOfWeek] . ')',
                        '', '', '', ''
                    ]);
                    continue;
                }

                [$breakTime, $workTime] = $this->calcBreakAndWork($a);

                fputcsv($out, [
                    $d->format('m/d') . '(' . $week[$d->dayOfWeek] . ')',
                    $a->clock_in_at ? Carbon::parse($a->clock_in_at)->format('H:i') : '',
                    $a->clock_out_at ? Carbon::parse($a->clock_out_at)->format('H:i') : '',
                    $breakTime,
                    $workTime,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function calcBreakAndWork(Attendance $a): array
    {
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

        $breakTime = $breakSeconds ? sprintf('%d:%02d', intdiv($breakSeconds, 3600), intdiv($breakSeconds % 3600, 60)) : '';
        $workTime  = $workSeconds ? sprintf('%d:%02d', intdiv($workSeconds, 3600), intdiv($workSeconds % 3600, 60)) : '';

        return [$breakTime, $workTime];
    }
}