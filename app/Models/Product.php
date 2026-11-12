<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;

#[Fillable(['name', 'cost_price', 'sell_price', 'stock', 'is_active'])]
class Product extends Model
{
    protected $attributes = ['stock' => 0, 'is_active' => true];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            Validator::make($model->getAttributes(), [
                'cost_price' => ['required', 'integer', 'min:0', 'max:4294967295'],
                'sell_price' => ['required', 'integer', 'min:0', 'max:4294967295'],
                'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            ])->validate();
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'cost_price' => 'integer',
            'sell_price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return HasMany<StockEntry, $this> */
    public function stockEntries(): HasMany
    {
        return $this->hasMany(StockEntry::class);
    }

    /** @return HasMany<SaleItem, $this> */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
