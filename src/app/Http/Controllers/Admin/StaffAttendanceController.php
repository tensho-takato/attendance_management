<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StaffAttendanceController extends Controller
{
    public function list(Request $request, User $user)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $month = $request->query('month', now()->format('Y-m')); // YYYY-MM
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $prevMonth = (clone $start)->subMonth()->format('Y-m');
        $nextMonth = (clone $start)->addMonth()->format('Y-m');

        // 対象月の勤怠 + 休憩
        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy('work_date');

        // その月の全日付（空白行を作るため）
        $dates = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $dates[] = $d->copy();
        }

        // 表示行を作る
        $rows = collect($dates)->map(function (Carbon $d) use ($attendances) {
            $workDate = $d->toDateString();
            $a = $attendances->get($workDate);

            if (! $a) {
                return [
                    'work_date_label' => $d->format('m/d') . '(' . $d->isoFormat('ddd') . ')',
                    'clock_in' => '',
                    'clock_out' => '',
                    'break_time' => '',
                    'work_time' => '',
                    'attendance_id' => null,
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

            $breakTime = $breakSeconds ? sprintf('%d:%02d', floor($breakSeconds / 3600), ($breakSeconds % 3600) / 60) : '';
            $workTime  = $workSeconds ? sprintf('%d:%02d', floor($workSeconds / 3600), ($workSeconds % 3600) / 60) : '';

            return [
                'work_date_label' => $d->format('m/d') . '(' . $d->isoFormat('ddd') . ')',
                'clock_in' => $a->clock_in_at ? Carbon::parse($a->clock_in_at)->format('H:i') : '',
                'clock_out' => $a->clock_out_at ? Carbon::parse($a->clock_out_at)->format('H:i') : '',
                'break_time' => $breakTime,
                'work_time' => $workTime,
                'attendance_id' => $a->id,
            ];
        });

        return view('admin.staff_attendance_index', compact(
            'user', 'month', 'prevMonth', 'nextMonth', 'rows'
        ));
    }

    public function csv(Request $request, User $user): StreamedResponse
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $month = $request->query('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $end   = (clone $start)->endOfMonth();

        $attendances = Attendance::with('breaks')
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('work_date')
            ->get()
            ->keyBy('work_date');

        $filename = sprintf('%s_%s.csv', $user->name, $month);

        return response()->streamDownload(function () use ($start, $end, $attendances) {
            $out = fopen('php://output', 'w');

            // Excelで文字化けしにくいように（必要なら）
            // fputs($out, "\xEF\xBB\xBF");

            fputcsv($out, ['日付', '出勤', '退勤', '休憩', '合計']);

            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $a = $attendances->get($d->toDateString());

                if (! $a) {
                    fputcsv($out, [$d->format('Y/m/d'), '', '', '', '']);
                    continue;
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

                $breakTime = $breakSeconds ? sprintf('%d:%02d', floor($breakSeconds / 3600), ($breakSeconds % 3600) / 60) : '';
                $workTime  = $workSeconds ? sprintf('%d:%02d', floor($workSeconds / 3600), ($workSeconds % 3600) / 60) : '';

                fputcsv($out, [
                    $d->format('Y/m/d'),
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
}