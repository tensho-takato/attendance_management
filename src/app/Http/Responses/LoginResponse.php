<?php

namespace App\Http\Responses;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        // 仕様書どおり：未認証なら常に verify
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return redirect('/email/verify');
        }

        // 管理者 → 管理者勤怠一覧
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return redirect()->intended('/admin/attendance/list');
        }

        // 一般 → 勤怠登録
        return redirect()->intended('/attendance');
    }
}