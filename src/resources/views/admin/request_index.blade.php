@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/request_index.css') }}">
@endsection

@section('content')
<main class="request-list">
    <h2>申請一覧</h2>

    <div class="request-tabs">
        <a href="{{ route('admin.scr.list', ['tab' => 'pending']) }}"
           class="{{ ($tab ?? 'pending') === 'pending' ? 'is-active' : '' }}">
            承認待ち
        </a>
        <a href="{{ route('admin.scr.list', ['tab' => 'approved']) }}"
           class="{{ ($tab ?? 'pending') === 'approved' ? 'is-active' : '' }}">
            承認済み
        </a>
    </div>

    <table>
        <thead>
        <tr>
            <th>状態</th>
            <th>名前</th>
            <th>対象日時</th>
            <th>申請理由</th>
            <th>申請日時</th>
            <th>詳細</th>
        </tr>
        </thead>

        <tbody>
        @forelse($requests as $r)
            <tr>
                <td>{{ (int)$r->status === 0 ? '承認待ち' : '承認済み' }}</td>
                <td>{{ $r->user?->name ?? '' }}</td>
                <td>{{ \Carbon\Carbon::parse($r->requested_work_date)->format('Y/m/d') }}</td>
                <td>{{ $r->note }}</td>
                <td>{{ $r->created_at?->format('Y/m/d') }}</td>
                <td>
                    <a href="{{ route('admin.scr.show', ['id' => $r->id]) }}">詳細</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6">申請はありません</td></tr>
        @endforelse
        </tbody>
    </table>
</main>
@endsection