@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff_attendance_index.css') }}">
@endsection

@section('content')
<main class="attendance-list">

    <h2 class="attendance-list__title">{{ $user->name }}さんの勤怠</h2>

    <div class="attendance-list__month-nav">
        <a href="{{ route('admin.attendance.staff', ['user' => $user->id, 'month' => $prevMonth]) }}">← 前月</a>

        <div class="attendance-list__month">
            <span>{{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('Y/m') }}</span>
        </div>

        <a href="{{ route('admin.attendance.staff', ['user' => $user->id, 'month' => $nextMonth]) }}">翌月 →</a>
    </div>

    <table class="attendance-list__table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>
        <tbody>
            @php
                $week = ['日', '月', '火', '水', '木', '金', '土'];
            @endphp

            @foreach($dates as $d)
                @php
                    $key = $d->toDateString();
                    $a = $attendances->get($key);

                    $clockIn = $a?->clock_in_at ? \Carbon\Carbon::parse($a->clock_in_at)->format('H:i') : '';
                    $clockOut = $a?->clock_out_at ? \Carbon\Carbon::parse($a->clock_out_at)->format('H:i') : '';

                    $breakSeconds = 0;

                    if ($a) {
                        foreach ($a->breaks as $b) {
                            if ($b->break_start_at && $b->break_end_at) {
                                $breakSeconds += \Carbon\Carbon::parse($b->break_end_at)
                                    ->diffInSeconds(\Carbon\Carbon::parse($b->break_start_at));
                            }
                        }
                    }

                    $workSeconds = 0;

                    if ($a && $a->clock_in_at && $a->clock_out_at) {
                        $workSeconds = \Carbon\Carbon::parse($a->clock_out_at)
                            ->diffInSeconds(\Carbon\Carbon::parse($a->clock_in_at)) - $breakSeconds;

                        if ($workSeconds < 0) {
                            $workSeconds = 0;
                        }
                    }

                    $breakTime = $breakSeconds
                        ? sprintf('%d:%02d', intdiv($breakSeconds, 3600), intdiv($breakSeconds % 3600, 60))
                        : '';

                    $workTime = $workSeconds
                        ? sprintf('%d:%02d', intdiv($workSeconds, 3600), intdiv($workSeconds % 3600, 60))
                        : '';
                @endphp

                <tr>
                    <td>{{ $d->format('m/d') }}({{ $week[$d->dayOfWeek] }})</td>
                    <td>{{ $clockIn }}</td>
                    <td>{{ $clockOut }}</td>
                    <td>{{ $breakTime }}</td>
                    <td>{{ $workTime }}</td>
                    <td>
                        @if($a)
                            <a href="{{ route('admin.attendance.show', ['id' => $a->id]) }}">詳細</a>
                        @else
                            <span></span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="actions">
        <a class="btn" href="{{ route('admin.staff.attendance.csv', ['user' => $user->id, 'month' => $month]) }}">CSV出力</a>
    </div>

</main>
@endsection