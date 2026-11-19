<?php

namespace App\Livewire\Stock;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $status = '';

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function toggleActive(mixed $id): void
    {
        User::activeAccount(admin: true);
        $data = Validator::make(['id' => $id], ['id' => ['required', 'integer', 'min:1', 'max:9223372036854775807']])->validate();
        DB::transaction(function () use ($data) {
            User::activeAccount(admin: true);
            $product = Product::query()->whereKey($data['id'])->lockForUpdate()->firstOrFail();
            $product->update(['is_active' => ! $product->is_active]);
        });
        $this->status = 'Status barang berhasil diubah.';
    }

    public function render(): View
    {
        User::activeAccount(admin: true);

        return view('livewire.stock.index', ['products' => Product::query()->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.trim($this->search).'%')))->orderBy('name')->orderBy('id')->paginate(20)])
            ->layout('layouts.app', ['title' => 'Stok']);
    }
}
