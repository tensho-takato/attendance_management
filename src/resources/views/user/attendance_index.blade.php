@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_index.css') }}">
@endsection

@section('content')
<main class="attendance-list">

    <h2 class="attendance-list__title">勤怠一覧</h2>

    <div class="attendance-list__month-nav">
        @php
            $current = \Carbon\Carbon::createFromFormat('Y-m', $month);
            $prev = $current->copy()->subMonth()->format('Y-m');
            $next = $current->copy()->addMonth()->format('Y-m');
        @endphp

        <a href="{{ route('attendance.list', ['month' => $prev]) }}">← 前月</a>

        <span class="attendance-list__month">
            {{ $current->format('Y/m') }}
        </span>

        <a href="{{ route('attendance.list', ['month' => $next]) }}">翌月 →</a>
    </div>

    <table class="attendance-list__table">
        <thead>
            <tr>
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th></th>
            </tr>
        </thead>

        <tbody>
            @foreach($dates as $day)
                @php
                    $workDate = $day->toDateString();     // YYYY-MM-DD
                    $a = $attendances->get($workDate);    // その日の勤怠（無ければ null）
                    $dateLabel = $day->isoFormat('MM/DD(ddd)');

                    $breakSeconds = 0;
                    $workSeconds = 0;

                    if ($a) {
                        foreach ($a->breaks as $b) {
                            if ($b->break_start_at && $b->break_end_at) {
                                $breakSeconds += \Carbon\Carbon::parse($b->break_end_at)
                                    ->diffInSeconds(\Carbon\Carbon::parse($b->break_start_at));
                            }
                        }

                        if ($a->clock_in_at && $a->clock_out_at) {
                            $workSeconds = \Carbon\Carbon::parse($a->clock_out_at)
                                ->diffInSeconds(\Carbon\Carbon::parse($a->clock_in_at)) - $breakSeconds;

                            if ($workSeconds < 0) $workSeconds = 0;
                        }
                    }

                    $breakTime = $breakSeconds ? sprintf('%d:%02d', floor($breakSeconds/3600), ($breakSeconds%3600)/60) : '';
                    $workTime  = $workSeconds ? sprintf('%d:%02d', floor($workSeconds/3600), ($workSeconds%3600)/60) : '';
                @endphp

                <tr>
                    <td>{{ $dateLabel }}</td>
                    <td>{{ $a && $a->clock_in_at ? \Carbon\Carbon::parse($a->clock_in_at)->format('H:i') : '' }}</td>
                    <td>{{ $a && $a->clock_out_at ? \Carbon\Carbon::parse($a->clock_out_at)->format('H:i') : '' }}</td>
                    <td>{{ $breakTime }}</td>
                    <td>{{ $workTime }}</td>
                    <td>
                        @if($a)
                            <a href="{{ route('attendance.detail', ['id' => $a->id]) }}">詳細</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

</main>
@endsection