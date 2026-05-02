@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/request_edit_approval.css') }}">
@endsection

@section('content')
<main class="request">
    <h2 class="request__title">勤怠詳細</h2>

    <div class="row">
        <div class="label">名前</div>
        <div class="field">
            <input type="text" value="{{ $requestItem->user?->name ?? '' }}" readonly>
        </div>
    </div>

    @php
        $d = \Carbon\Carbon::parse($requestItem->requested_work_date);
    @endphp

    <div class="row row--date">
        <div class="label">日付</div>
        <div class="field time-range">
            <input type="text" value="{{ $d->format('Y') }}年" readonly>
            <span class="tilde" style="opacity:0;">〜</span>
            <input type="text" value="{{ $d->format('n月j日') }}" readonly>
        </div>
    </div>

    <div class="row">
        <div class="label">出勤・退勤</div>
        <div class="field time-range">
            <input type="text" value="{{ $requestItem->requested_clock_in_at?->format('H:i') }}" readonly>
            <span>〜</span>
            <input type="text" value="{{ $requestItem->requested_clock_out_at?->format('H:i') }}" readonly>
        </div>
    </div>

    <div class="row">
        <div class="label">休憩</div>
        <div class="field time-range">
            <input type="text" value="{{ $breakRows[0]['start'] }}" readonly>
            <span>〜</span>
            <input type="text" value="{{ $breakRows[0]['end'] }}" readonly>
        </div>
    </div>

    <div class="row">
        <div class="label">休憩2</div>
        <div class="field time-range">
            <input type="text" value="{{ $breakRows[1]['start'] }}" readonly>
            <span>〜</span>
            <input type="text" value="{{ $breakRows[1]['end'] }}" readonly>
        </div>
    </div>

    <div class="row">
        <div class="label">備考</div>
        <div class="field">
            <textarea rows="3" readonly>{{ $requestItem->note }}</textarea>
        </div>
    </div>

    <div class="actions">
        @if((int)$requestItem->status === 1)
            <button type="button" class="btn btn--approved" disabled>承認済み</button>
        @else
            <form method="POST" action="{{ route('admin.scr.approve', ['id' => $requestItem->id]) }}">
                @csrf
                <button type="submit" class="btn">承認</button>
            </form>
        @endif
    </div>
</main>
@endsection