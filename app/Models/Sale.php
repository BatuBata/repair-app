<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;

#[Fillable(['number', 'sold_on', 'customer_name', 'customer_phone', 'warranty_days', 'warranty_until', 'warranty_contacted_at', 'created_by'])]
class Sale extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            Validator::make($model->getAttributes(), [
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
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
