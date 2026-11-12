<?php

namespace App\Http\Responses;

use App\Models\User;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $request->session()->forget('url.intended');

        $user = User::activeAccount();
        $request->session()->put('password_hash_web', auth()->guard('web')->hashPasswordForCookie($user->password));

        return redirect($user->homePath());
    }
}
