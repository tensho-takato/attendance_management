@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_create.css') }}">
@endsection

@section('content')

<main class="attendance">
    {{-- 例: $status = off|working|break|finished --}}
    <p class="attendance_badge">
        @switch($status)
            @case('off') 勤務外 @break
            @case('working') 出勤中 @break
            @case('break') 休憩中 @break
            @case('finished') 退勤済 @break
        @endswitch
    </p>

    <p class="attendance_date">{{ $dateLabel }}</p>
    <p class="attendance_time">{{ $timeLabel }}</p>

    {{-- ボタン出し分け --}}
    @if($status === 'off')
        <form method="POST" action="{{ route('attendance.clockIn') }}">
            @csrf
            <button type="submit" class="attendance_primary_button">出勤</button>
        </form>

    @elseif($status === 'working')
        <div class="attendance_buttons">
            <form method="POST" action="{{ route('attendance.clockOut') }}">
                @csrf
                <button type="submit" class="attendance_primary_button">退勤</button>
            </form>

            <form method="POST" action="{{ route('attendance.breakIn') }}">
                @csrf
                <button type="submit" class="attendance_secondary_button">休憩入</button>
            </form>
        </div>

    @elseif($status === 'break')
        <form method="POST" action="{{ route('attendance.breakOut') }}">
            @csrf
            <button type="submit" class="attendance_secondary_button">休憩戻</button>
        </form>

    @elseif($status === 'finished')
        <p class="attendance_message">お疲れ様でした。</p>
    @endif
</main>
@endsection
