<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
    <title>@yield('title', 'attendance')</title>
</head>
<body>
    <header class="header">
        <div class="header_inner">
            <img class="logo" src="{{ asset('images/header_logo.png') }}" alt="coachtech">
        </div>

        <nav>
            @auth
                @if(auth()->user()->isAdmin())
                    <ul class="header-nav">
                        <li><a href="{{ route('admin.attendance.list') }}">勤怠一覧</a></li>
                        <li><a href="{{ route('admin.staff.list') }}">スタッフ一覧</a></li>
                        <li><a href="{{ route('admin.scr.list') }}">申請一覧</a></li>
                        <li>
                            <form method="POST" action="{{ url('/logout') }}">
                                @csrf
                                <input type="hidden" name="logout_type" value="admin">
                                <button type="submit" class="header-nav__button">ログアウト</button>
                            </form>
                        </li>
                    </ul>
                @else
                    <ul class="header-nav">
                        <li><a href="{{ route('attendance.index') }}">勤怠</a></li>
                        <li><a href="{{ route('attendance.list') }}">勤怠一覧</a></li>
                        <li><a href="{{ route('scr.list', ['tab' => 'pending']) }}">申請</a></li>
                        <li>
                            <form method="POST" action="{{ url('/logout') }}">
                                @csrf
                                <button type="submit" class="header-nav__button">ログアウト</button>
                            </form>
                        </li>
                    </ul>
                @endif
            @endauth
        </nav>
    </header>

    @yield('content')
</body>
</html>