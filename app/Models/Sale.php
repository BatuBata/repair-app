<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Support\PhoneNumber;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * @property int $tenant_id
 * @property ?string $payment_method
 * @property ?string $payment_bank
 * @property ?CarbonImmutable $paid_at
 */
#[Fillable(['number', 'sold_on', 'customer_id', 'customer_name', 'customer_phone', 'warranty_days', 'warranty_until', 'warranty_contacted_at', 'payment_method', 'payment_bank', 'paid_at', 'created_by'])]
class Sale extends Model
{
    use BelongsToTenant;

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $tenantId = app(TenantContext::class)->id();
            abort_if($model->exists && ((int) $model->tenant_id !== $tenantId || (int) $model->getRawOriginal('tenant_id') !== $tenantId), 404);
            Validator::make($model->getAttributes(), [
                'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
                'created_by' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
                'warranty_days' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            ])->validate();
            if ($model->customer_phone !== null) {
                $model->customer_phone = PhoneNumber::normalize($model->customer_phone);
            }
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'sold_on' => 'immutable_date',
            'warranty_days' => 'integer',
            'warranty_until' => 'immutable_date',
            'warranty_contacted_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
