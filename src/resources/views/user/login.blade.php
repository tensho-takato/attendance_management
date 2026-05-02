<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>login</title>
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
        <h2 class="login_title">ログイン</h2>

        <form class="form" method="POST" action="{{ url('/login') }}">
            @csrf
            <input type="hidden" name="login_type" value="user">

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
                <button class="form_button-submit" type="submit">ログインする</button>
            </div>
        </form>

        <div class="register__link">
            <a class="register__button-submit" href="/register">会員登録はこちら</a>
        </div>
    </main>
</body>
</html>