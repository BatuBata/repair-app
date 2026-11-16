<?php

namespace App\Models\Concerns;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $context = app(TenantContext::class);
            $context->has()
                ? $builder->where($builder->qualifyColumn('tenant_id'), $context->id())
                : $builder->whereRaw('1 = 0');
        });

        static::creating(function (Model $model): void {
            $model->setAttribute('tenant_id', app(TenantContext::class)->id());
        });
    }
}
