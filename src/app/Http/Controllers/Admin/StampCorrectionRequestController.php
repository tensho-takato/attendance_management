<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StampCorrectionRequest;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StampCorrectionRequestController extends Controller
{
    // 申請一覧（管理者）
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $tab = $request->query('tab', 'pending'); // pending / approved
        $status = $tab === 'approved' ? 1 : 0;

        $requests = StampCorrectionRequest::with(['user', 'attendance'])
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.request_index', compact('requests', 'tab'));
    }

    // 申請詳細（承認画面）
    public function show($id)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

            $requestItem = StampCorrectionRequest::with(['user', 'attendance', 'breaks'])
                ->findOrFail($id);

            $breaks = $requestItem->breaks()->orderBy('id')->take(2)->get();

            $breakRows = [
                [
                    'start' => $breaks->get(0)?->break_start_at ? \Carbon\Carbon::parse($breaks->get(0)->break_start_at)->format('H:i') : '',
                    'end'   => $breaks->get(0)?->break_end_at   ? \Carbon\Carbon::parse($breaks->get(0)->break_end_at)->format('H:i') : '',
                ],
                [
                    'start' => $breaks->get(1)?->break_start_at ? \Carbon\Carbon::parse($breaks->get(1)->break_start_at)->format('H:i') : '',
                    'end'   => $breaks->get(1)?->break_end_at   ? \Carbon\Carbon::parse($breaks->get(1)->break_end_at)->format('H:i') : '',
                ],
            ];

            return view('admin.request_edit_approval', compact('requestItem', 'breakRows'));
    }

    // 承認（勤怠へ反映）
    public function approve($id)
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $requestItem = StampCorrectionRequest::with(['attendance', 'breaks'])
            ->findOrFail($id);

        // すでに承認済みなら何もしない（多重クリック対策）
        if ((int)$requestItem->status === 1) {
            return redirect()->route('admin.scr.show', ['id' => $requestItem->id]);
        }

        DB::transaction(function () use ($requestItem) {
            $attendance = Attendance::lockForUpdate()->findOrFail($requestItem->attendance_id);

            // 勤怠を申請内容で更新
            $attendance->update([
                'work_date'    => $requestItem->requested_work_date,
                'clock_in_at'  => $requestItem->requested_clock_in_at,
                'clock_out_at' => $requestItem->requested_clock_out_at,
                'note'         => $requestItem->note,
            ]);

            // 休憩は「申請の内容」に置き換える
            $attendance->breaks()->delete();

            foreach ($requestItem->breaks as $b) {
                // 両方空は作らない（念のため）
                if (!$b->break_start_at && !$b->break_end_at) continue;

                $attendance->breaks()->create([
                    'break_start_at' => $b->break_start_at,
                    'break_end_at'   => $b->break_end_at,
                ]);
            }

            // 申請を承認済みに
            $requestItem->update([
                'status'      => 1,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });

        return redirect()->route('admin.scr.show', ['id' => $requestItem->id]);
    }
}