@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance_index.css') }}">
@endsection

@section('content')
<main class="attendance-list">

    <h2 class="attendance-list__title">{{ $currentDate->format('Y年n月j日') }}の勤怠</h2>

    {{-- 日付ナビ --}}
    <div class="attendance-list__date-nav">
        <a class="attendance-list__date-nav-link"
           href="{{ route('admin.attendance.list', ['date' => $prevDate->toDateString()]) }}">
            ← 前日
        </a>

        <div class="attendance-list__date-nav-center">
            <span class="attendance-list__calendar-icon">📅</span>
            <span class="attendance-list__date">{{ $currentDate->format('Y/m/d') }}</span>
        </div>

        <a class="attendance-list__date-nav-link"
           href="{{ route('admin.attendance.list', ['date' => $nextDate->toDateString()]) }}">
            翌日 →
        </a>
    </div>

    <table class="attendance-list__table">
        <thead>
            <tr>
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
        </thead>

        <tbody>
        @forelse($rows as $row)
            <tr>
                <td>{{ $row['name'] }}</td>
                <td>{{ $row['clock_in'] }}</td>
                <td>{{ $row['clock_out'] }}</td>
                <td>{{ $row['break_time'] }}</td>
                <td>{{ $row['work_time'] }}</td>
                <td>
                    @if($row['attendance_id'])
                        <a href="{{ route('admin.attendance.show', ['id' => $row['attendance_id']]) }}">詳細</a>
                    @else
                        {{-- 勤怠が無いユーザーは詳細へ行けないので空白 --}}
                        <span></span>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">表示するデータがありません</td>
            </tr>
        @endforelse
        </tbody>
    </table>

</main>
@endsection