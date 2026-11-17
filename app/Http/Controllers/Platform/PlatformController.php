<?php

namespace App\Http\Controllers\Platform;

use App\Actions\CreateTenant;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class PlatformController extends Controller
{
    public function loginForm(Request $request): View
    {
        $this->ensureConfigured();
        abort_if($request->user() !== null, 403);

        return view('platform.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $this->ensureConfigured();
        abort_if($request->user() !== null, 403);
        $data = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        $username = Str::lower(trim($data['username']));
        $key = 'platform-login:'.hash('sha256', $username.'|'.$request->ip());
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Terlalu banyak percobaan masuk. Coba lagi nanti.');

        if (! hash_equals((string) config('repair.platform_owner.username'), $username)
            || ! password_verify($data['password'], (string) config('repair.platform_owner.password_hash'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['username' => 'Username atau password salah.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('platform_owner', $this->fingerprint());

        return redirect()->route('platform.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login');
    }

    public function index(): View
    {
        $tenants = Tenant::query()->orderBy('created_at')->orderBy('id')->get();
        $owners = User::withoutGlobalScope('tenant')
            ->whereIn('tenant_id', $tenants->modelKeys())
            ->where('role', UserRole::Admin)
            ->orderBy('id')
            ->get(['tenant_id', 'name', 'username'])
            ->unique('tenant_id')
            ->keyBy('tenant_id');

        return view('platform.index', compact('tenants', 'owners'));
    }

    public function store(Request $request, CreateTenant $createTenant): RedirectResponse
    {
        try {
            $tenant = $createTenant->create($request->only('store_name', 'name', 'username', 'password', 'password_confirmation'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['tenant' => 'Toko gagal dibuat. Seluruh perubahan dibatalkan.']);
        }

        return redirect()->route('platform.index')->with('status', "Toko {$tenant->name} berhasil dibuat.");
    }

    public function toggle(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => ! $tenant->is_active]);

        return redirect()->route('platform.index')->with('status', 'Status toko berhasil diperbarui.');
    }

    private function ensureConfigured(): void
    {
        abort_if((string) config('repair.platform_owner.username') === ''
            || (string) config('repair.platform_owner.password_hash') === '', 503, 'Login owner platform belum dikonfigurasi.');
    }

    private function fingerprint(): string
    {
        return hash('sha256', config('repair.platform_owner.username')."\0".config('repair.platform_owner.password_hash'));
    }
}
