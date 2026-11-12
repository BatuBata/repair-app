<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Validator;

#[Fillable(['spent_on', 'category', 'description', 'amount', 'created_by'])]
class Expense extends Model
{
    protected static function booted(): void
    {
        static::saving(function (self $model) {
            Validator::make($model->getAttributes(), [
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
