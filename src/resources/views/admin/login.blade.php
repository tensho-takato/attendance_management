<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>admin_login</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/login.css') }}" />
</head>
<body>

    <header class="header">
        <div class="header_inner">
            <img class="logo" src="{{ asset('images/header_logo.png') }}" alt="coachtech">
        </div>
    </header>

    <main>
        <h2 class="login_title">管理者ログイン</h2>

        <form class="form" method="POST" action="{{ url('/login') }}">
            @csrf
            <input type="hidden" name="login_type" value="admin">

            <div class="form_group">
                <div class="form_group-label">メールアドレス</div>
                <div class="form__group-content">
                    <input type="email" name="email" value="{{ old('email') }}">
                    @error('email')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form_group">
                <div class="form_group-label">パスワード</div>
                <div class="form__group-content">
                    <input type="password" name="password">
                    @error('password')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form_button">
                <button class="form_button-submit" type="submit">管理者ログインする</button>
            </div>
        </form>
    </main>
</body>
</html>