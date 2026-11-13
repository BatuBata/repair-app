<?php

namespace App\Actions\ServiceOrders;

use App\Enums\ServiceItemType;
use App\Models\ServiceOrder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class InvoiceLines
{
    /** @param array<mixed> $rows
     * @return array{rows: list<array{type: string, description: string, qty: int, price: int, cost: int}>, price: int, cost: int, profit: int}
     */
    public static function calculate(array $rows): array
    {
        foreach ($rows as &$row) {
            if (is_array($row) && ($row['type'] ?? null) === 'jasa') {
                $row['cost'] = 0;
            }
        }
        unset($row);
        $data = Validator::make(['rows' => $rows], [
            'rows' => ['array', 'list'], 'rows.*' => ['array'],
            'rows.*.description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'rows.*.type' => ['required', Rule::enum(ServiceItemType::class)],
            'rows.*.qty' => ['required', 'integer', 'min:1', 'max:4294967295'],
            'rows.*.price' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'rows.*.cost' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ], [
            'required' => ':attribute wajib diisi.', 'integer' => ':attribute harus berupa bilangan bulat.',
            'min' => ':attribute minimal :min.', 'max' => ':attribute maksimal :max.',
            'array' => ':attribute tidak valid.', 'list' => ':attribute harus berupa daftar.',
            'enum' => ':attribute tidak valid.', 'string' => ':attribute harus berupa teks.',
        ], ['rows' => 'Rincian pekerjaan', 'rows.*.qty' => 'Qty', 'rows.*.price' => 'Harga jual', 'rows.*.cost' => 'Modal', 'rows.*.type' => 'Jenis', 'rows.*.description' => 'Deskripsi'])->validate();
        $clean = [];
        $price = $cost = 0;
        foreach ($data['rows'] as $i => $row) {
            $qty = (int) $row['qty'];
            $unitPrice = (int) $row['price'];
            $unitCost = (int) $row['cost'];
            if ($unitPrice > intdiv(PHP_INT_MAX, $qty) || $unitCost > intdiv(PHP_INT_MAX, $qty)) {
                throw ValidationException::withMessages(["rows.$i.qty" => 'Subtotal melampaui batas perhitungan.']);
            }
            $sale = $qty * $unitPrice;
            $capital = $qty * $unitCost;
            if ($sale > PHP_INT_MAX - $price || $capital > PHP_INT_MAX - $cost) {
                throw ValidationException::withMessages(['rows' => 'Total nota melampaui batas perhitungan.']);
            }
            $price += $sale;
            $cost += $capital;
            $clean[] = ['type' => $row['type'], 'description' => (string) ($row['description'] ?? ''), 'qty' => $qty, 'price' => $unitPrice, 'cost' => $unitCost];
        }

        return ['rows' => $clean, 'price' => $price, 'cost' => $cost, 'profit' => $price - $cost];
    }

    public static function revision(ServiceOrder $order): string
    {
        return hash('sha256', json_encode([$order->getAttributes(), $order->items()->orderBy('id')->get()->toArray()], JSON_THROW_ON_ERROR));
    }
}
