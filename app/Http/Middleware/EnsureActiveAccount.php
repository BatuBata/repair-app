<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->id() !== null) {
            $user = User::find(auth()->id());
            if (! $user || ! $user->is_active) {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                abort(403, 'Akun tidak aktif. Hubungi admin toko.');
            }
            auth()->setUser($user);
        }

        return $next($request);
    }
}
