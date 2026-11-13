<?php

namespace App\Livewire\Stock;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Form extends Component
{
    #[Locked]
    public ?int $productId = null;

    public string $name = '';

    public string $cost_price = '';

    public string $sell_price = '';

    public string $stock = '0';

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function mount(mixed $id = null): void
    {
        User::activeAccount(admin: true);
        if ($id !== null) {
            $data = Validator::make(['id' => $id], ['id' => ['required', 'integer', 'min:1', 'max:9223372036854775807']])->validate();
            $product = Product::query()->whereKey($data['id'])->firstOrFail();
            $this->productId = $product->id;
            $this->name = $product->name;
            $this->cost_price = (string) $product->cost_price;
            $this->sell_price = (string) $product->sell_price;
            $this->stock = (string) $product->stock;
        }
    }

    public function save(): void
    {
        User::activeAccount(admin: true);
        $this->name = trim($this->name);
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'cost_price' => ['required', 'integer', 'between:0,4294967295'],
            'sell_price' => ['required', 'integer', 'between:0,4294967295'],
        ];
        if ($this->productId === null) {
            $rules['stock'] = ['required', 'integer', 'between:0,4294967295'];
        }
        $data = $this->validate($rules, [
            'required' => ':attribute wajib diisi.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'between' => ':attribute harus antara 0 dan 4.294.967.295.',
            'name.max' => 'Nama barang maksimal 255 karakter.',
        ], ['name' => 'Nama barang', 'cost_price' => 'Harga beli', 'sell_price' => 'Harga jual', 'stock' => 'Stok awal']);

        DB::transaction(function () use ($data) {
            User::activeAccount(admin: true);
            if ($this->productId === null) {
                Product::create($data);
            } else {
                Product::query()->lockForUpdate()->findOrFail($this->productId)->update($data);
            }
        });
        session()->flash('stock-status', 'Barang berhasil disimpan.');
        $this->redirectRoute('stock.index');
    }

    public function render(): View
    {
        User::activeAccount(admin: true);

        return view('livewire.stock.form')->layout('layouts.app', ['title' => $this->productId ? 'Ubah barang' : 'Tambah barang']);
    }
}
