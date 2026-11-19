<?php

namespace App\Models;

use App\Enums\ServiceOrderStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Support\PhoneNumber;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * @property int $tenant_id
 * @property ?string $payment_method
 * @property ?string $payment_bank
 * @property ?CarbonImmutable $paid_at
 */
#[Fillable(['number', 'customer_id', 'customer_name', 'customer_phone', 'customer_address', 'category_id', 'item_description', 'complaint', 'status', 'created_by', 'technician_id', 'assigned_at', 'inspection_started_at', 'work_started_at', 'inspection_result', 'action_plan', 'invoiced_on', 'warranty_days', 'warranty_until', 'unrepairable', 'discount', 'picked_up_on', 'cancelled_at', 'cancel_reason', 'warranty_contacted_at', 'tracking_token', 'payment_method', 'payment_bank', 'paid_at', 'created_at'])]
class ServiceOrder extends Model
{
    use BelongsToTenant;

    protected $attributes = ['status' => 'diterima', 'unrepairable' => false];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->tracking_token = Str::random(64);
        });
        static::saving(function (self $model) {
            $tenantId = app(TenantContext::class)->id();
            abort_if($model->exists && ((int) $model->tenant_id !== $tenantId || (int) $model->getRawOriginal('tenant_id') !== $tenantId), 404);
            Validator::make($model->getAttributes(), [
                'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
                'category_id' => ['required', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)],
                'created_by' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
                'technician_id' => ['nullable', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
                'warranty_days' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            ])->validate();
            $model->customer_phone = PhoneNumber::normalize($model->customer_phone);
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ServiceOrderStatus::class,
            'discount' => 'integer',
            'assigned_at' => 'immutable_datetime',
            'inspection_started_at' => 'immutable_datetime',
            'work_started_at' => 'immutable_datetime',
            'invoiced_on' => 'immutable_date',
            'warranty_days' => 'integer',
            'warranty_until' => 'immutable_date',
            'unrepairable' => 'boolean',
            'picked_up_on' => 'immutable_date',
            'cancelled_at' => 'immutable_datetime',
            'warranty_contacted_at' => 'immutable_datetime',
            'paid_at' => 'immutable_datetime',
        ];
    }

    public function inspectionNotes(): string
    {
        $notes = trim($this->inspection_result ?? '');
        if (filled($this->action_plan)) {
            $notes .= ($notes !== '' ? "\n\n" : '')."Rencana tindakan:\n".$this->action_plan;
        }

        return $notes;
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /** @return HasMany<ServiceOrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class);
    }
}
