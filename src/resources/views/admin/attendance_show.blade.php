@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance_show.css') }}">
@endsection

@section('content')
<main class="request">

  <h2 class="request__title">勤怠詳細</h2>

  <form method="POST" action="{{ route('admin.attendance.update', ['id' => $attendance->id]) }}">
    @csrf

    <div class="row">
      <div class="label">名前</div>
      <div class="field">
        <input type="text" value="{{ $userName }}" readonly>
      </div>
    </div>

    @php
      $d = \Carbon\Carbon::parse(old('work_date', $attendance->work_date));
    @endphp

<div class="row row--date">
  <div class="label">日付</div>

  <div class="field field--split">
    <input
      type="text"
      name="work_year"
      value="{{ old('work_year', $d->format('Y')) }}"
      inputmode="numeric"
      class="date-year-input"
    >
    <span class="tilde"></span>
    <input
      type="text"
      name="work_md"
      value="{{ old('work_md', $d->format('n/j')) }}"
      inputmode="numeric"
      class="date-md-input"
    >
  </div>

  @error('work_date')
    <p class="form-error">{{ $message }}</p>
  @enderror
</div>

    <div class="row">
      <div class="label">出勤・退勤</div>
      <div class="field time-range">
        <input type="time" name="clock_in_at" value="{{ old('clock_in_at', $clockIn) }}">
        <span>〜</span>
        <input type="time" name="clock_out_at" value="{{ old('clock_out_at', $clockOut) }}">
      </div>

      @if($errors->has('clock_out_at') || $errors->has('clock_in_at'))
        <p class="form-error">
          {{ $errors->first('clock_out_at') ?: $errors->first('clock_in_at') }}
        </p>
      @endif
    </div>

    <div class="row">
      <div class="label">休憩</div>
      <div class="field time-range">
        <input type="time" name="breaks[0][break_start_at]" value="{{ old('breaks.0.break_start_at', $break1Start) }}">
        <span>〜</span>
        <input type="time" name="breaks[0][break_end_at]" value="{{ old('breaks.0.break_end_at', $break1End) }}">
      </div>
      @error('breaks.0')
        <p class="form-error">{{ $message }}</p>
      @enderror
    </div>

    <div class="row">
      <div class="label">休憩2</div>
      <div class="field time-range">
        <input type="time" name="breaks[1][break_start_at]" value="{{ old('breaks.1.break_start_at', $break2Start) }}">
        <span>〜</span>
        <input type="time" name="breaks[1][break_end_at]" value="{{ old('breaks.1.break_end_at', $break2End) }}">
      </div>
      @error('breaks.1')
        <p class="form-error">{{ $message }}</p>
      @enderror
    </div>

    <div class="row">
      <div class="label">備考</div>
      <div class="field">
        <textarea name="note" rows="3">{{ old('note', $attendance->note) }}</textarea>
        @error('note')
          <p class="form-error">{{ $message }}</p>
        @enderror
      </div>
    </div>

    <div class="actions">
      <button type="submit" class="btn">修正</button>
    </div>

  </form>
</main>
@endsection