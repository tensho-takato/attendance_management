<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    protected function redirectTo($request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // admin配下にアクセス → 管理者ログインへ
        if ($request->is('admin/*')) {
            return route('admin.login');
        }

        // それ以外 → 一般ログインへ
        return route('login'); // Fortifyの /login
    }
}