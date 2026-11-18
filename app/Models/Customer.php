<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\PhoneNumber;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $tenant_id
 * @property string $name
 * @property string $phone
 * @property ?string $address
 */
#[Fillable(['name', 'phone', 'address'])]
class Customer extends Model
{
    use BelongsToTenant;

    /** @return HasMany<ServiceOrder, $this> */
    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class);
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $customer) {
            $tenantId = app(TenantContext::class)->id();
            abort_if($customer->exists && ((int) $customer->tenant_id !== $tenantId || (int) $customer->getRawOriginal('tenant_id') !== $tenantId), 404);
            $customer->phone = PhoneNumber::normalize($customer->phone);
        });
    }
}
