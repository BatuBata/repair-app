<?php

namespace App\Models;

use App\Enums\ServiceOrderStatus;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

#[Fillable(['number', 'customer_name', 'customer_phone', 'customer_address', 'category_id', 'item_description', 'complaint', 'status', 'created_by', 'technician_id', 'assigned_at', 'invoiced_on', 'warranty_days', 'warranty_until', 'unrepairable', 'picked_up_on', 'cancelled_at', 'cancel_reason', 'warranty_contacted_at', 'tracking_token', 'created_at'])]
class ServiceOrder extends Model
{
    protected $attributes = ['status' => 'diterima', 'unrepairable' => false];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->tracking_token = Str::random(64);
        });
        static::saving(function (self $model) {
            Validator::make($model->getAttributes(), [
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
            'assigned_at' => 'immutable_datetime',
            'invoiced_on' => 'immutable_date',
            'warranty_days' => 'integer',
            'warranty_until' => 'immutable_date',
            'unrepairable' => 'boolean',
            'picked_up_on' => 'immutable_date',
            'cancelled_at' => 'immutable_datetime',
            'warranty_contacted_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
