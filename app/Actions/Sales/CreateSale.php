<?php

namespace App\Actions\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\DocumentNumber;
use App\Support\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class CreateSale
{
    /** @param array<string, mixed> $input */
    public function handle(array $input): Sale
    {
        User::activeAccount(admin: true);
        $data = Validator::make($input, [
            'sold_on' => ['required', 'date_format:Y-m-d'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'customer_phone' => ['nullable', 'string', 'max:40'],
            'warranty_days' => ['nullable', 'integer', 'between:0,4294967295'],
            'items' => ['required', 'array', 'list', 'min:1', 'max:100'],
            'items.*.product_id' => ['required', 'integer', 'min:1', 'max:9223372036854775807', 'distinct'],
            'items.*.qty' => ['required', 'integer', 'between:1,4294967295'],
            'items.*.price' => ['required', 'integer', 'between:0,4294967295'],
        ], [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'between' => ':attribute di luar batas yang diperbolehkan.',
            'date_format' => 'Tanggal jual harus berupa tanggal yang valid.',
            'items.min' => 'Tambahkan minimal satu barang.',
            'items.max' => 'Maksimal 100 baris barang per penjualan.',
            'items.*.product_id.distinct' => 'Barang yang sama hanya boleh dipilih satu kali. Gabungkan jumlahnya.',
            'customer_name.max' => 'Nama customer maksimal 100 karakter.',
        ], ['sold_on' => 'Tanggal jual', 'customer_name' => 'Nama customer', 'customer_phone' => 'Nomor HP', 'warranty_days' => 'Hari garansi', 'items' => 'Barang', 'items.*.product_id' => 'Barang', 'items.*.qty' => 'Jumlah', 'items.*.price' => 'Harga jual'])->validate();
        $data['customer_name'] = trim($data['customer_name'] ?? '') ?: null;
        $phone = trim($data['customer_phone'] ?? '');
        $data['customer_phone'] = $phone === '' ? null : PhoneNumber::normalize($phone);
        $days = (int) ($data['warranty_days'] ?? 0);
        if ($days > 0 && $data['customer_phone'] === null) {
            throw ValidationException::withMessages(['customer_phone' => 'Nomor HP wajib diisi untuk penjualan bergaransi.']);
        }
        $soldOn = CarbonImmutable::createFromFormat('!Y-m-d', $data['sold_on'], 'Asia/Makassar');
        if ($days > $soldOn->diffInDays(CarbonImmutable::create(9999, 12, 31, 0, 0, 0, 'Asia/Makassar'))) {
            throw ValidationException::withMessages(['warranty_days' => 'Masa garansi melewati batas tanggal 31/12/9999.']);
        }

        return DB::transaction(function () use ($data, $soldOn, $days) {
            $admin = User::activeAccount(admin: true);
            $products = Product::query()->whereIn('id', array_column($data['items'], 'product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $items = [];
            $totalPrice = 0;
            $totalCost = 0;
            foreach ($data['items'] as $index => $row) {
                $product = $products->get($row['product_id']);
                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages(["items.$index.product_id" => 'Pilih barang aktif yang tersedia.']);
                }
                $qty = (int) $row['qty'];
                $price = (int) $row['price'];
                if ($qty > $product->stock) {
                    throw ValidationException::withMessages(["items.$index.qty" => 'Stok tidak cukup. Tersedia '.number_format($product->stock, 0, ',', '.').' unit.']);
                }
                if ($price > intdiv(PHP_INT_MAX, $qty) || $product->cost_price > intdiv(PHP_INT_MAX, $qty)) {
                    throw ValidationException::withMessages(["items.$index.qty" => 'Nilai baris terlalu besar. Kurangi jumlah atau harga.']);
                }
                $subtotal = $qty * $price;
                $cost = $qty * $product->cost_price;
                if ($subtotal > PHP_INT_MAX - $totalPrice || $cost > PHP_INT_MAX - $totalCost) {
                    throw ValidationException::withMessages(["items.$index.qty" => 'Total penjualan terlalu besar. Pisahkan transaksi.']);
                }
                $totalPrice += $subtotal;
                $totalCost += $cost;
                $items[] = ['product_id' => $product->id, 'product_name' => $product->name, 'qty' => $qty, 'price' => $price, 'cost' => $product->cost_price];
                $product->update(['stock' => $product->stock - $qty]);
            }
            $createdAt = CarbonImmutable::now('Asia/Makassar');
            $sale = new Sale([
                'number' => app(DocumentNumber::class)->next('JL', $createdAt),
                'sold_on' => $soldOn->toDateString(),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'warranty_days' => $days,
                'warranty_until' => $days > 0 ? $soldOn->addDays($days)->toDateString() : null,
                'created_by' => $admin->id,
            ]);
            $sale->created_at = $createdAt;
            $sale->save();
            $sale->items()->createMany($items);

            return $sale;
        }, 5);
    }
}
