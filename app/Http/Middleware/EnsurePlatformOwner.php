<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = (string) config('repair.platform_owner.username');
        $passwordHash = (string) config('repair.platform_owner.password_hash');
        abort_if($username === '' || $passwordHash === '', 503, 'Login owner platform belum dikonfigurasi.');
        abort_if($request->user() !== null, 403);

        $expected = hash('sha256', $username."\0".$passwordHash);
        abort_unless(hash_equals($expected, (string) $request->session()->get('platform_owner')), 403);

        return $next($request);
    }
}
