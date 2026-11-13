<?php

namespace App\Livewire\Stock;

use App\Models\Product;
use App\Models\StockEntry;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Restock extends Component
{
    #[Locked]
    public int $productId;

    public string $qty = '';

    public string $cost_price = '';

    public string $entry_date = '';

    public string $note = '';

    public string $status = '';

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function mount(mixed $id): void
    {
        User::activeAccount(admin: true);
        $data = Validator::make(['id' => $id], ['id' => ['required', 'integer', 'min:1', 'max:9223372036854775807']])->validate();
        $product = Product::query()->whereKey($data['id'])->firstOrFail();
        $this->productId = $product->id;
        $this->cost_price = (string) $product->cost_price;
        $this->entry_date = now()->toDateString();
    }

    public function save(): void
    {
        User::activeAccount(admin: true);
        $data = $this->validate([
            'qty' => ['required', 'integer', 'between:1,4294967295'],
            'cost_price' => ['required', 'integer', 'between:0,4294967295'],
            'entry_date' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:1000'],
        ], [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'qty.between' => 'Jumlah masuk harus antara 1 dan 4.294.967.295.',
            'cost_price.between' => 'Harga beli harus antara 0 dan 4.294.967.295.',
            'entry_date.date_format' => 'Tanggal masuk harus berupa tanggal yang valid.',
            'note.max' => 'Catatan maksimal 1.000 karakter.',
        ], ['qty' => 'Jumlah masuk', 'cost_price' => 'Harga beli', 'entry_date' => 'Tanggal masuk', 'note' => 'Catatan']);

        DB::transaction(function () use ($data) {
            $admin = User::activeAccount(admin: true);
            $product = Product::query()->lockForUpdate()->findOrFail($this->productId);
            if (! $product->is_active) {
                throw ValidationException::withMessages(['qty' => 'Aktifkan barang sebelum menambah stok.']);
            }
            if ($product->stock + (int) $data['qty'] > 4294967295) {
                throw ValidationException::withMessages(['qty' => 'Stok akhir melebihi batas 4.294.967.295 unit.']);
            }
            $product->update(['stock' => $product->stock + (int) $data['qty'], 'cost_price' => $data['cost_price']]);
            StockEntry::create([...$data, 'product_id' => $product->id, 'created_by' => $admin->id]);
        });
        $this->reset(['qty', 'note']);
        $this->status = 'Restok berhasil disimpan. Stok dan harga beli sudah diperbarui.';
    }

    public function render(): View
    {
        User::activeAccount(admin: true);
        $product = Product::findOrFail($this->productId);

        return view('livewire.stock.restock', [
            'product' => $product,
            'entries' => $product->stockEntries()->with('creator')->orderByDesc('entry_date')->orderByDesc('id')->get(),
        ])->layout('layouts.app', ['title' => 'Restok barang']);
    }
}
