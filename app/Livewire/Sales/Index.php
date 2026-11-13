<?php

namespace App\Livewire\Sales;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Index extends Component
{
    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function render(): View
    {
        User::activeAccount(admin: true);

        return view('livewire.sales.index', ['sales' => Sale::query()->with('items')->orderByDesc('sold_on')->orderByDesc('id')->get()])
            ->layout('layouts.app', ['title' => 'Penjualan']);
    }
}
