<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    #[Locked]
    public int $customerId;

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function mount(int $id): void
    {
        $this->customerId = Customer::findOrFail($id)->id;
    }

    public function render(): View
    {
        User::activeAccount(admin: true);
        $customer = Customer::findOrFail($this->customerId);

        return view('livewire.customers.show', [
            'customer' => $customer,
            'orders' => ServiceOrder::query()->with('category')->where(function ($query) use ($customer) {
                $query->where('customer_id', $customer->id)->orWhere(function ($legacy) use ($customer) {
                    $legacy->whereNull('customer_id')->where('customer_phone', $customer->phone);
                });
            })
                ->orderByDesc('created_at')->orderByDesc('id')->paginate(20),
            'sales' => Sale::query()->with('items')->where(function ($query) use ($customer) {
                $query->where('customer_id', $customer->id)->orWhere(function ($legacy) use ($customer) {
                    $legacy->whereNull('customer_id')->where('customer_phone', $customer->phone);
                });
            })->orderByDesc('sold_on')->orderByDesc('id')->paginate(20, pageName: 'salesPage'),
        ])->layout('layouts.app', ['title' => 'Pelanggan '.$customer->name]);
    }
}
