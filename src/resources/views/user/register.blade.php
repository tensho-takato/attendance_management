<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/register.css') }}" />
</head>
<body>
    <header class="header">
        <div class="header_inner">
            <img class="logo" src="{{ asset('images/header_logo.png') }}" alt="coachtech">
        </div>
    </header>

    <main>
        <h2 class="register_title">会員登録</h2>

        <form class="form" method="POST" action="{{ url('/register') }}">
            @csrf

            <div class="form_group">
                <div class="form_group-label">名前</div>
                <div class="form__group-content">
                    <input type="text" name="name" value="{{ old('name') }}">
                    @error('name')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

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

            <div class="form_group">
                <div class="form_group-label">パスワード確認</div>
                <div class="form__group-content">
                    <input type="password" name="password_confirmation">
                    @error('password_confirmation')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form_button">
                <button class="form_button-submit" type="submit">登録する</button>
            </div>
        </form>

        <div class="login__link">
            <a class="login__button-submit" href="/login">ログインはこちら</a>
        </div>
    </main>
</body>
</html>