<?php

namespace App\Console\Commands;

use App\Actions\CreateTenant as CreateTenantAction;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateTenant extends Command
{
    protected $signature = 'tenant:create';

    protected $description = 'Membuat toko dan akun admin owner secara interaktif.';

    public function handle(CreateTenantAction $createTenant): int
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
        $data['password_confirmation'] = $data['password'];

        try {
            $createTenant->create($data);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        } catch (Throwable) {
            $this->error('Toko gagal dibuat. Seluruh perubahan dibatalkan.');

            return self::FAILURE;
        }

        $this->info('Toko dan akun owner berhasil dibuat. Isi pengaturan toko, kategori, dan teknisi setelah login.');

        return self::SUCCESS;
    }
}
