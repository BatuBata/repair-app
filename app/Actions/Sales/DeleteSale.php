<?php

namespace App\Actions\Sales;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class DeleteSale
{
    public function handle(mixed $id): void
    {
        User::activeAccount(admin: true);
        $data = Validator::make(['id' => $id], ['id' => ['required', 'integer', 'min:1', 'max:9223372036854775807']])->validate();
        DB::transaction(function () use ($data) {
            User::activeAccount(admin: true);
            $sale = Sale::query()->whereKey($data['id'])->lockForUpdate()->firstOrFail();
            $items = $sale->items()->get();
            $products = Product::query()->whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($items as $item) {
                $product = $products->get($item->product_id);
                abort_unless($product !== null, 409, 'Barang penjualan tidak ditemukan.');
                if ($item->qty > 4294967295 - $product->stock) {
                    throw ValidationException::withMessages(['sale' => 'Stok setelah pengembalian melewati batas. Periksa stok barang sebelum menghapus penjualan.']);
                }
                $product->update(['stock' => $product->stock + $item->qty]);
            }
            $sale->delete();
        }, 5);
    }
}
