<?php

namespace App\Http\Controllers;

use App\Http\Requests\StampCorrectionRequestStoreRequest;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StampCorrectionRequestController extends Controller
{
    public function store(StampCorrectionRequestStoreRequest $request, Attendance $attendance)
    {
        abort_unless($attendance->user_id === auth()->id(), 403);

        $exists = StampCorrectionRequest::where('attendance_id', $attendance->id)
            ->where('status', 0)
            ->exists();

        if ($exists) {
            return redirect()->route('attendance.detail', ['id' => $attendance->id]);
        }

        $date = $request->work_date;

        DB::transaction(function () use ($request, $attendance, $date) {
            $scr = StampCorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'user_id'       => auth()->id(),
                'status'        => 0,

                'requested_work_date' => $date,
                'requested_clock_in_at'  => Carbon::parse("$date {$request->clock_in_at}"),
                'requested_clock_out_at' => Carbon::parse("$date {$request->clock_out_at}"),

                'note' => $request->note,

                'approved_by' => null,
                'approved_at' => null,
            ]);

            foreach ($request->input('breaks', []) as $b) {
                $bs = $b['break_start_at'] ?? null;
                $be = $b['break_end_at'] ?? null;

                if (!$bs && !$be) continue;

                $scr->breaks()->create([
                    'break_start_at' => $bs ? Carbon::parse("$date $bs") : null,
                    'break_end_at'   => $be ? Carbon::parse("$date $be") : null,
                ]);
            }
        });

        return redirect()->route('scr.list', ['tab' => 'pending']);
    }

    public function list(Request $request)
    {
        $tab = $request->query('tab', 'pending');
        $status = $tab === 'approved' ? 1 : 0;

        $requests = StampCorrectionRequest::with('user')
            ->where('user_id', auth()->id())
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->get();

        return view('user.request_index', compact('requests', 'tab'));
    }
}