<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenant
{
    public function __construct(private TenantContext $context) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $tenantId = filter_var($user->getAttribute('tenant_id'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $tenant = $tenantId === false ? null : Tenant::query()->find($tenantId);
        if (! $tenant?->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            abort(403, 'Toko tidak aktif. Hubungi pengelola aplikasi.');
        }

        $this->context->set($tenant);

        try {
            return $next($request);
        } finally {
            $this->context->forget();
        }
    }
}
