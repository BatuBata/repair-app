<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

#[Fillable(['name', 'address', 'phone', 'logo_path', 'warranty_terms', 'default_warranty_days'])]
class StoreSetting extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $attributes = ['default_warranty_days' => 30];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            Validator::make($model->getAttributes(), [
                'default_warranty_days' => ['required', 'integer', 'min:0', 'max:4294967295'],
            ])->validate();
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'default_warranty_days' => 'integer',
        ];
    }
}
