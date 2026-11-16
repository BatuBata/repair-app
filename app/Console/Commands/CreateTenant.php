<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\StoreSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateTenant extends Command
{
    protected $signature = 'tenant:create';

    protected $description = 'Membuat toko dan akun admin owner secara interaktif.';

    public function handle(TenantContext $context): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Jalankan tenant:create secara interaktif.');

            return self::FAILURE;
        }

        $data = [
            'store_name' => trim((string) $this->ask('Nama toko')),
            'name' => trim((string) $this->ask('Nama owner')),
            'username' => Str::lower(trim((string) $this->ask('Username owner'))),
            'password' => $this->secret('Password awal (minimal 9 karakter)', false),
        ];

        try {
            Validator::make($data, [
                'store_name' => ['required', 'string', 'max:255'],
                'name' => ['required', 'string', 'max:255'],
                'username' => ['required', 'string', 'max:100', 'regex:/\A[a-z0-9._-]+\z/', 'unique:users,username'],
                'password' => ['required', 'string', Password::default()],
            ])->validate();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $previousTenant = $context->has() ? $context->tenant() : null;
        try {
            DB::transaction(function () use ($context, $data): void {
                $tenant = Tenant::create(['name' => $data['store_name']]);
                $context->set($tenant);
                StoreSetting::create(['name' => '', 'address' => '', 'phone' => '']);
                User::create([
                    'name' => $data['name'],
                    'username' => $data['username'],
                    'password' => $data['password'],
                    'role' => UserRole::Admin,
                    'is_active' => true,
                ]);
            });
        } catch (Throwable) {
            $this->error('Toko gagal dibuat. Seluruh perubahan dibatalkan.');

            return self::FAILURE;
        } finally {
            $previousTenant ? $context->set($previousTenant) : $context->forget();
        }

        $this->info('Toko dan akun owner berhasil dibuat. Isi pengaturan toko, kategori, dan teknisi setelah login.');

        return self::SUCCESS;
    }
}
