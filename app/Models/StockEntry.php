<?php

namespace App\Models;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Fillable(['product_id', 'qty', 'cost_price', 'entry_date', 'note', 'created_by'])]
class StockEntry extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $tenantId = app(TenantContext::class)->id();
            if ($model->exists) {
                Product::query()->findOrFail($model->getRawOriginal('product_id'));
            }
            Validator::make($model->getAttributes(), [
                'product_id' => ['required', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
                'created_by' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
                'qty' => ['required', 'integer', 'min:1', 'max:4294967295'],
                'cost_price' => ['required', 'integer', 'min:0', 'max:4294967295'],
            ])->validate();
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'cost_price' => 'integer',
            'entry_date' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
