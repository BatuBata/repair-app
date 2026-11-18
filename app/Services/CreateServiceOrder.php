<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\ServiceOrder;
use App\Models\User;
use App\Support\PhoneNumber;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreateServiceOrder
{
    /** @param array<string, mixed> $input */
    public function create(array $input, User $creator): ServiceOrder
    {
        $tenantId = app(TenantContext::class)->id();
        abort_unless(User::query()->whereKey($creator->id)->exists(), 404);
        $data = Validator::make($input, [
            'customer_id' => ['nullable', 'integer', 'min:1', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'customer_name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string'],
            'customer_address' => ['nullable', 'string'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('tenant_id', $tenantId)->where('is_active', true)],
            'item_description' => ['nullable', 'string'],
            'complaint' => ['required', 'string', 'max:500'],
        ])->validate();
        $data['customer_phone'] = PhoneNumber::normalize($data['customer_phone']);

        return DB::transaction(function () use ($data, $creator) {
            if (! empty($data['customer_id'])) {
                $customer = Customer::query()->whereKey($data['customer_id'])->lockForUpdate()->firstOrFail();
                $data['customer_name'] = $customer->name;
                $data['customer_phone'] = $customer->phone;
                $data['customer_address'] = $customer->address;
            }
            $createdAt = CarbonImmutable::now('Asia/Makassar');

            return ServiceOrder::create([
                ...$data,
                'created_by' => $creator->id,
                'number' => app(DocumentNumber::class)->next('TTS', $createdAt),
                'created_at' => $createdAt,
            ]);
        }, 5);
    }
}
