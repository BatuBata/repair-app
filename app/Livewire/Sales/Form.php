<?php

namespace App\Livewire\Sales;

use App\Actions\Sales\CreateSale;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class Form extends Component
{
    public string $sold_on = '';

    public string $customer_name = '';

    public string $customer_id = '';

    public string $customer_phone = '';

    public string $warranty_days = '0';

    public string $discount = '0';

    public string $payment_method = 'cash';

    public string $payment_bank = '';

    /** @var list<array{product_id: string, qty: string, price: string}> */
    public array $items = [['product_id' => '', 'qty' => '1', 'price' => '']];

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function mount(): void
    {
        User::activeAccount(admin: true);
        $this->sold_on = now()->toDateString();
    }

    public function addItem(): void
    {
        User::activeAccount(admin: true);
        abort_if(count($this->items) >= 100, 422);
        $this->items[] = ['product_id' => '', 'qty' => '1', 'price' => ''];
    }

    public function updatedCustomerId(): void
    {
        User::activeAccount(admin: true);
        $this->resetValidation();
        if ($this->customer_id === '') {
            $this->reset(['customer_name', 'customer_phone']);

            return;
        }
        $customer = Customer::findOrFail($this->customer_id);
        $this->customer_name = $customer->name;
        $this->customer_phone = $customer->phone;
    }

    public function newCustomer(): void
    {
        $this->customer_id = '';
        $this->updatedCustomerId();
    }

    public function removeItem(mixed $index): void
    {
        User::activeAccount(admin: true);
        $data = Validator::make(['index' => $index], ['index' => ['required', 'integer', 'min:0', 'max:99']])->validate();
        abort_unless(array_key_exists($data['index'], $this->items), 404);
        $items = $this->items;
        unset($items[$data['index']]);
        $this->items = array_values($items);
        $this->resetValidation();
    }

    public function selectProduct(mixed $index, mixed $id): void
    {
        User::activeAccount(admin: true);
        $data = Validator::make(['index' => $index, 'id' => $id], [
            'index' => ['required', 'integer', 'min:0', 'max:99'],
            'id' => ['required', 'integer', 'min:1', 'max:9223372036854775807'],
        ])->validate();
        abort_unless(array_key_exists($data['index'], $this->items), 404);
        $product = Product::query()->whereKey($data['id'])->where('is_active', true)->firstOrFail();
        $this->items[$data['index']]['product_id'] = (string) $product->id;
        $this->items[$data['index']]['price'] = (string) $product->sell_price;
        $this->resetValidation();
    }

    public function save(): void
    {
        User::activeAccount(admin: true);
        $input = $this->only(['sold_on', 'customer_name', 'customer_phone', 'warranty_days', 'discount', 'payment_method', 'payment_bank', 'items']);
        $input['customer_id'] = $this->customer_id === '' ? null : $this->customer_id;
        $sale = app(CreateSale::class)->handle($input);
        session()->flash('sale-status', 'Penjualan berhasil disimpan.');
        $this->redirectRoute('sales.show', $sale->id);
    }

    protected function previewSubtotal(): ?int
    {
        $total = 0;
        foreach ($this->items as $row) {
            if ($row['product_id'] === '') {
                continue;
            }
            if (! ctype_digit($row['qty']) || ! ctype_digit($row['price']) || (int) $row['qty'] < 1 || (int) $row['qty'] > 4294967295 || (int) $row['price'] > 4294967295) {
                return null;
            }
            $qty = (int) $row['qty'];
            $price = (int) $row['price'];
            if ($price > intdiv(PHP_INT_MAX, $qty) || $qty * $price > PHP_INT_MAX - $total) {
                return null;
            }
            $total += $qty * $price;
        }

        return $total;
    }

    protected function previewTotal(?int $subtotal): ?int
    {
        if ($subtotal === null || Validator::make(['discount' => $this->discount], ['discount' => ['required', 'integer', 'min:0', 'max:'.$subtotal]])->fails()) {
            return null;
        }

        return $subtotal - (int) $this->discount;
    }

    public function render(): View
    {
        User::activeAccount(admin: true);

        Validator::make(['items' => $this->items], [
            'items' => ['array', 'list', 'max:100'],
            'items.*' => ['array:product_id,qty,price'],
            'items.*.product_id' => ['present', 'string'],
            'items.*.qty' => ['present', 'string'],
            'items.*.price' => ['present', 'string'],
        ])->validate();

        $previewSubtotal = $this->previewSubtotal();

        return view('livewire.sales.form', ['products' => Product::query()->where('is_active', true)->orderBy('name')->get(), 'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'phone']), 'previewSubtotal' => $previewSubtotal, 'previewTotal' => $this->previewTotal($previewSubtotal)])
            ->layout('layouts.app', ['title' => 'Penjualan baru']);
    }
}
