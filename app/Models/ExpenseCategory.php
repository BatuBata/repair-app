<?php

namespace App\Models;

use App\Enums\ExpenseCategory as DefaultCategory;
use App\Models\Concerns\BelongsToTenant;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $tenant_id
 * @property string $name
 * @property bool $is_active
 */
#[Fillable(['name', 'is_active'])]
class ExpenseCategory extends Model
{
    use BelongsToTenant;

    protected $attributes = ['is_active' => true];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return list<string> */
    public static function defaultNames(): array
    {
        return array_column(DefaultCategory::cases(), 'value');
    }

    public function isDefault(): bool
    {
        return in_array(mb_strtolower($this->name), self::defaultNames(), true);
    }

    /** @return list<string> */
    public static function activeNames(): array
    {
        $configured = self::query()->orderBy('name')->get();
        $active = $configured->pluck('is_active', 'name');

        return array_values(collect(self::defaultNames())->merge($configured->map(fn (self $category): string => $category->name))->unique()
            ->filter(fn ($name) => $active->get($name, true))->all());
    }

    protected static function booted(): void
    {
        static::saving(function (self $category) {
            $tenantId = app(TenantContext::class)->id();
            abort_if($category->exists && ((int) $category->tenant_id !== $tenantId || (int) $category->getRawOriginal('tenant_id') !== $tenantId), 404);
            if ($category->exists && $category->isDirty('name')) {
                abort_if(in_array(mb_strtolower($category->getRawOriginal('name')), self::defaultNames(), true) || $category->isDefault(), 403);
            }
            if (! $category->exists && $category->isDefault()) {
                $category->name = mb_strtolower($category->name);
            }
        });
        static::deleting(function (self $category) {
            $tenantId = app(TenantContext::class)->id();
            abort_unless((int) $category->tenant_id === $tenantId && (int) $category->getRawOriginal('tenant_id') === $tenantId, 404);
            abort_if(in_array(mb_strtolower($category->getRawOriginal('name')), self::defaultNames(), true), 403);
        });
    }
}
