@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/staff_index.css') }}">
@endsection

@section('content')
<main class="staff-list">
    <h2 class="staff-list__title">スタッフ一覧</h2>

    <table class="staff-list__table">
        <thead>
            <tr>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
        </thead>

        <tbody>
        @forelse($users as $u)
            <tr>
                <td>{{ $u->name }}</td>
                <td>{{ $u->email }}</td>
                <td>
                    <a href="{{ route('admin.attendance.staff', ['user' => $u->id, 'month' => now()->format('Y-m')]) }}">
                        詳細
                    </a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="3">表示するデータがありません</td>
            </tr>
        @endforelse
        </tbody>
    </table>
</main>
@endsection