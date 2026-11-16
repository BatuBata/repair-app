<?php

namespace App\Support;

use App\Models\Tenant;
use LogicException;

final class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function has(): bool
    {
        return $this->tenant !== null;
    }

    public function tenant(): Tenant
    {
        return $this->tenant ?? throw new LogicException('Tenant aktif belum ditetapkan.');
    }

    public function id(): int
    {
        return (int) $this->tenant()->getKey();
    }

    public function forget(): void
    {
        $this->tenant = null;
    }
}
