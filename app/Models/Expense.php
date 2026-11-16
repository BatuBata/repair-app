<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Models\Concerns\BelongsToTenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** @property int $tenant_id */
#[Fillable(['spent_on', 'category', 'description', 'amount', 'created_by'])]
class Expense extends Model
{
    use BelongsToTenant;

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $tenantId = app(TenantContext::class)->id();
            abort_if($model->exists && ((int) $model->tenant_id !== $tenantId || (int) $model->getRawOriginal('tenant_id') !== $tenantId), 404);
            Validator::make($model->getAttributes(), [
                'created_by' => ['required', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
                'amount' => ['required', 'integer', 'min:1', 'max:4294967295'],
            ])->validate();
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'spent_on' => 'immutable_date',
            'category' => ExpenseCategory::class,
            'amount' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
