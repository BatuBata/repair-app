<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;

#[Fillable(['sale_id', 'product_id', 'product_name', 'qty', 'price', 'cost'])]
class SaleItem extends Model
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
            'qty' => 'integer',
            'price' => 'integer',
            'cost' => 'integer',
        ];
    }

    /** @return BelongsTo<Sale, $this> */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
