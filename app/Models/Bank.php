<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\Payment;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $tenant_id
 * @property string $name
 * @property bool $is_active
 */
#[Fillable(['name', 'is_active'])]
class Bank extends Model
{
    use BelongsToTenant;

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function isDefault(): bool
    {
        return in_array($this->name, Payment::BANKS, true);
    }

    protected static function booted(): void
    {
        static::saving(function (self $bank) {
            $tenantId = app(TenantContext::class)->id();
            abort_if($bank->exists && ((int) $bank->tenant_id !== $tenantId || (int) $bank->getRawOriginal('tenant_id') !== $tenantId), 404);
            abort_if($bank->exists && in_array($bank->getRawOriginal('name'), Payment::BANKS, true) && $bank->isDirty('name'), 403);
        });
        static::deleting(function (self $bank) {
            $tenantId = app(TenantContext::class)->id();
            abort_unless((int) $bank->tenant_id === $tenantId && (int) $bank->getRawOriginal('tenant_id') === $tenantId, 404);
            abort_if(in_array($bank->getRawOriginal('name'), Payment::BANKS, true), 403);
        });
    }
}
