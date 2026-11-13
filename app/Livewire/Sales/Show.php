<?php

namespace App\Livewire\Sales;

use App\Actions\Sales\DeleteSale;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Show extends Component
{
    #[Locked]
    public int $saleId;

    #[Locked]
    public bool $confirmingDelete = false;

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function mount(mixed $id): void
    {
        User::activeAccount(admin: true);
        $data = Validator::make(['id' => $id], ['id' => ['required', 'integer', 'min:1', 'max:9223372036854775807']])->validate();
        $this->saleId = Sale::query()->whereKey($data['id'])->firstOrFail()->id;
    }

    public function confirmDelete(): void
    {
        User::activeAccount(admin: true);
        $this->confirmingDelete = true;
    }

    public function cancelDelete(): void
    {
        User::activeAccount(admin: true);
        $this->confirmingDelete = false;
    }

    public function delete(): void
    {
        User::activeAccount(admin: true);
        abort_unless($this->confirmingDelete, 422);
        app(DeleteSale::class)->handle($this->saleId);
        session()->flash('sale-status', 'Penjualan dihapus. Stok barang sudah dikembalikan.');
        $this->redirectRoute('sales.index');
    }

    public function render(): View
    {
        User::activeAccount(admin: true);
        $sale = Sale::query()->with(['items', 'creator'])->findOrFail($this->saleId);

        return view('livewire.sales.show', ['sale' => $sale])->layout('layouts.app', ['title' => $sale->number]);
    }
}
