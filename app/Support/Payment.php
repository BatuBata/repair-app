<?php

namespace App\Support;

use App\Models\Bank;
use App\Models\Sale;
use App\Models\ServiceOrder;
use Illuminate\Validation\Rule;

final class Payment
{
    public const METHODS = [
        'cash' => 'Tunai',
        'qris' => 'QRIS',
        'transfer' => 'Transfer bank',
    ];

    public const BANKS = ['BCA', 'BNI', 'BRI', 'Bank Mandiri', 'BSI', 'Bank Kaltimtara', 'CIMB Niaga', 'PermataBank', 'Lainnya'];

    /** @param array<string, mixed> $input
     * @return array{payment_method: string, payment_bank: ?string}
     */
    public static function validate(array $input): array
    {
        $data = validator($input, [
            'payment_method' => ['required', Rule::in(array_keys(self::METHODS))],
            'payment_bank' => ['nullable', 'string', 'max:50', Rule::requiredIf(($input['payment_method'] ?? null) === 'transfer'), Rule::in(array_column(self::bankOptions(), 'value')), Rule::prohibitedIf(($input['payment_method'] ?? null) !== 'transfer')],
        ], [
            'payment_method.required' => 'Pilih metode pembayaran.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
            'payment_bank.required' => 'Pilih bank tujuan transfer.',
            'payment_bank.in' => 'Bank tujuan transfer tidak valid.',
            'payment_bank.prohibited' => 'Bank hanya boleh dipilih untuk transfer.',
        ])->validate();

        return ['payment_method' => $data['payment_method'], 'payment_bank' => $data['payment_method'] === 'transfer' ? $data['payment_bank'] : null];
    }

    public static function label(?string $method, ?string $bank = null): string
    {
        if ($method === null) {
            return 'Belum dicatat';
        }

        return self::METHODS[$method].($method === 'transfer' && $bank ? ' · '.$bank : '');
    }

    /** @return list<array{value: string, label: string}> */
    public static function methodOptions(bool $all = false): array
    {
        $options = $all ? [['value' => 'semua', 'label' => 'Semua metode']] : [];

        foreach (self::METHODS as $value => $label) {
            $options[] = compact('value', 'label');
        }

        return $options;
    }

    /** @return list<array{value: string, label: string}> */
    public static function bankOptions(bool $all = false): array
    {
        $options = $all ? [['value' => 'semua', 'label' => 'Semua bank']] : [];
        $configured = Bank::query()->orderBy('name')->get();
        $active = $configured->pluck('is_active', 'name');
        $banks = collect(self::BANKS)->merge($configured->pluck('name'))->unique();
        if ($all) {
            $banks = $banks->merge(ServiceOrder::query()->whereNotNull('payment_bank')->distinct()->pluck('payment_bank'))
                ->merge(Sale::query()->whereNotNull('payment_bank')->distinct()->pluck('payment_bank'))->unique();
        }

        foreach ($banks as $bank) {
            if (! $all && $active->get($bank, true) === false) {
                continue;
            }
            $options[] = ['value' => $bank, 'label' => $bank];
        }

        return $options;
    }
}
