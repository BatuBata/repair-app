<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function render(): View
    {
        User::activeAccount(admin: true);

        return view('livewire.sales.index', ['sales' => Sale::query()->with('items')->when(trim($this->search) !== '', fn ($query) => $query->where(fn ($query) => $query->where('number', 'like', '%'.trim($this->search).'%')->orWhere('customer_name', 'like', '%'.trim($this->search).'%')->orWhere('customer_phone', 'like', '%'.trim($this->search).'%')))->orderByDesc('sold_on')->orderByDesc('id')->paginate(20)])
            ->layout('layouts.app', ['title' => 'Penjualan']);
    }
}
