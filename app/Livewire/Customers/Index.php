<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\User;
use App\Support\PhoneNumber;
use App\Support\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $customerId = null;

    #[Locked]
    public bool $editing = false;

    public string $name = '';

    public string $customer_phone = '';

    public string $address = '';

    public string $search = '';

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        User::activeAccount(admin: true);
        $this->cancel();
        $this->editing = true;
    }

    public function edit(int $id): void
    {
        User::activeAccount(admin: true);
        $customer = Customer::findOrFail($id);
        $this->resetValidation();
        $this->customerId = $customer->id;
        $this->name = $customer->name;
        $this->customer_phone = $customer->phone;
        $this->address = $customer->address ?? '';
        $this->editing = true;
    }

    public function cancel(): void
    {
        User::activeAccount(admin: true);
        $this->reset(['customerId', 'editing', 'name', 'customer_phone', 'address']);
        $this->resetValidation();
    }

    public function save(): void
    {
        User::activeAccount(admin: true);
        abort_unless($this->editing, 422);
        $this->name = trim($this->name);
        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'customer_phone' => ['required', 'string', 'max:40'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);
        $this->customer_phone = PhoneNumber::normalize($this->customer_phone);
        $this->validate(['customer_phone' => [Rule::unique('customers', 'phone')->where('tenant_id', app(TenantContext::class)->id())->ignore($this->customerId)]], [
            'customer_phone.unique' => 'Nomor HP sudah terdaftar sebagai pelanggan.',
        ]);
        $customer = $this->customerId === null ? new Customer : Customer::findOrFail($this->customerId);
        $customer->fill(['name' => $this->name, 'phone' => $this->customer_phone, 'address' => $this->address])->save();
        $this->cancel();
        session()->flash('customer_status', 'Pelanggan berhasil disimpan.');
    }

    public function render(): View
    {
        User::activeAccount(admin: true);

        return view('livewire.customers.index', [
            'customers' => Customer::query()->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')->orWhere('phone', 'like', '%'.$this->search.'%');
            })->orderBy('name')->paginate(20),
        ])->layout('layouts.app', ['title' => 'Pelanggan']);
    }
}
