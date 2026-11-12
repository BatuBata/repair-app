<?php

namespace App\Models;

use App\Enums\ServiceItemType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;

#[Fillable(['service_order_id', 'type', 'description', 'qty', 'price', 'cost'])]
class ServiceOrderItem extends Model
{
    public function subtotalPrice(): int
    {
        return $this->qty * $this->price;
    }

    public function subtotalCost(): int
    {
        return $this->qty * $this->cost;
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if ($model->getAttributes()['type'] === ServiceItemType::Jasa->value) {
                $model->cost = 0;
            }
            Validator::make($model->getAttributes(), [
                'qty' => ['required', 'integer', 'min:1', 'max:4294967295'],
                'price' => ['required', 'integer', 'min:0', 'max:4294967295'],
                'cost' => ['required', 'integer', 'min:0', 'max:4294967295'],
            ])->validate();
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ServiceItemType::class,
            'qty' => 'integer',
            'price' => 'integer',
            'cost' => 'integer',
        ];
    }

    /** @return BelongsTo<ServiceOrder, $this> */
    public function serviceOrder(): BelongsTo
    {
        return $this->belongsTo(ServiceOrder::class);
    }
}
