<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        // 仕様書どおり：登録直後は未認証なので /email/verify に飛ばす
        return redirect('/email/verify');
    }
}