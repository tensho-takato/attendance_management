@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance_show.css') }}">
@endsection

@section('content')
<main class="request">

  <h2 class="request__title">勤怠詳細</h2>

  <form method="POST" action="{{ route('scr.store', ['attendance' => $attendance->id]) }}">
    @csrf

    {{-- 名前（編集不可・枠なし） --}}
    <div class="row">
      <div class="label">名前</div>
      <div class="field">
        <input type="text" value="{{ $userName ?? auth()->user()->name }}" readonly>
      </div>
    </div>

    {{-- 日付（編集可：年 + 月日） --}}
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

  @if($errors->has('work_date') || $errors->has('work_year') || $errors->has('work_md'))
    <p class="form-error">
      {{ $errors->first('work_date') ?: $errors->first('work_year') ?: $errors->first('work_md') }}
    </p>
  @endif
</div>

    {{-- 出勤・退勤（編集可） --}}
    <div class="row">
      <div class="label">出勤・退勤</div>
      <div class="field field--split">
        <input type="time" name="clock_in_at" value="{{ old('clock_in_at', $clockIn) }}" {{ $hasPendingRequest ? 'disabled' : '' }}>
        <span class="tilde">〜</span>
        <input type="time" name="clock_out_at" value="{{ old('clock_out_at', $clockOut) }}" {{ $hasPendingRequest ? 'disabled' : '' }}>
      </div>

      @if($errors->has('clock_out_at') || $errors->has('clock_in_at'))
        <p class="form-error">
          {{ $errors->first('clock_out_at') ?: $errors->first('clock_in_at') }}
        </p>
      @endif
    </div>

    {{-- 休憩（編集可） --}}
    <div class="row">
      <div class="label">休憩</div>
      <div class="field field--split">
        <input type="time" name="breaks[0][break_start_at]" value="{{ old('breaks.0.break_start_at', $break1Start) }}" {{ $hasPendingRequest ? 'disabled' : '' }}>
        <span class="tilde">〜</span>
        <input type="time" name="breaks[0][break_end_at]" value="{{ old('breaks.0.break_end_at', $break1End) }}" {{ $hasPendingRequest ? 'disabled' : '' }}>
      </div>

      @error('breaks.0')
        <p class="form-error">{{ $message }}</p>
      @enderror
    </div>

    {{-- 休憩2（編集可） --}}
    <div class="row">
      <div class="label">休憩2</div>
      <div class="field field--split">
        <input type="time" name="breaks[1][break_start_at]" value="{{ old('breaks.1.break_start_at', $break2Start) }}" {{ $hasPendingRequest ? 'disabled' : '' }}>
        <span class="tilde">〜</span>
        <input type="time" name="breaks[1][break_end_at]" value="{{ old('breaks.1.break_end_at', $break2End) }}" {{ $hasPendingRequest ? 'disabled' : '' }}>
      </div>

      @error('breaks.1')
        <p class="form-error">{{ $message }}</p>
      @enderror
    </div>

    {{-- 備考（編集可） --}}
    <div class="row">
      <div class="label">備考</div>
      <div class="field">
        <textarea name="note" rows="3" {{ $hasPendingRequest ? 'disabled' : '' }}>{{ old('note', $attendance->note) }}</textarea>
        @error('note')
          <p class="form-error">{{ $message }}</p>
        @enderror
      </div>
    </div>

    {{-- actions --}}
    <div class="actions">
      @if($hasPendingRequest)
        <p class="request-warning">※承認待ちのため修正はできません</p>
      @else
        <button type="submit" class="btn">修正</button>
      @endif
    </div>

  </form>
</main>
@endsection