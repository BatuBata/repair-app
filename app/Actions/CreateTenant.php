<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\StoreSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

final class CreateTenant
{
    public function __construct(private TenantContext $context) {}

    /** @param array<string, mixed> $input */
    public function create(array $input): Tenant
    {
        if (is_string($input['store_name'] ?? null)) {
            $input['store_name'] = trim($input['store_name']);
        }
        if (is_string($input['name'] ?? null)) {
            $input['name'] = trim($input['name']);
        }
        if (is_string($input['username'] ?? null)) {
            $input['username'] = Str::lower(trim($input['username']));
        }

        $data = Validator::make($input, [
            'store_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:100', 'regex:/\A[a-z0-9._-]+\z/', 'unique:users,username'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ])->validate();

        $previousTenant = $this->context->has() ? $this->context->tenant() : null;

        try {
            return DB::transaction(function () use ($data): Tenant {
                $tenant = Tenant::create(['name' => $data['store_name']]);
                $this->context->set($tenant);
                StoreSetting::create(['name' => '', 'address' => '', 'phone' => '']);
                User::create([
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'password' => $data['password'],
                    'role' => UserRole::Admin,
                    'is_active' => true,
                ]);

                return $tenant;
            });
        } finally {
            $previousTenant ? $this->context->set($previousTenant) : $this->context->forget();
        }
    }
}
