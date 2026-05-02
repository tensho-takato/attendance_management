<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>verify</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/verify-email.css') }}" />
</head>
<body>
    <header class="header">
        <div class="header_inner">
            <img class="logo" src="{{ asset('images/header_logo.png') }}" alt="coachtech">
        </div>
    </header>

    <main class="main">
        <div class="card">
            <p class="text">
                登録していただいたメールアドレスに認証メールを送付しました。<br>
                メール認証を完了してください。
            </p>

            <div class="actions">
                <a class="btn" href="http://localhost:8025" target="_blank" rel="noopener">認証はこちらから</a>
            </div>

            <form method="POST" action="{{ url('/email/verification-notification') }}">
                @csrf
                <button type="submit" class="link-btn">認証メールを再送する</button>
            </form>
        </div>
    </main>
</body>
</html>