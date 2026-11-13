<?php

namespace App\Livewire\Tts;

use App\Enums\ServiceOrderStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Support\PhoneNumber;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Form extends Component
{
    #[Locked]
    public ?int $orderId = null;

    public string $customer_name = '';

    public string $customer_phone = '';

    public string $category_id = '';

    public string $complaint = '';

    public string $item_description = '';

    public string $customer_address = '';

    public function boot(): void
    {
        User::activeAccount();
    }

    public function mount(?int $id = null): void
    {
        $this->orderId = $id;
        if ($id !== null) {
            $order = $this->editableOrder();
            foreach (['customer_name', 'customer_phone', 'complaint', 'item_description', 'customer_address', 'category_id'] as $field) {
                $this->{$field} = (string) $order->getAttribute($field);
            }
        }
    }

    protected function editableOrder(bool $lock = false): ServiceOrder
    {
        $user = User::activeAccount();
        $order = ServiceOrder::query()->when($lock, fn ($query) => $query->lockForUpdate())->findOrFail($this->orderId);
        abort_unless($user->role === UserRole::Admin || ($order->created_by === $user->id && $order->getAttribute('status') === ServiceOrderStatus::Diterima), 403);

        return $order;
    }

    public function save(): void
    {
        $order = DB::transaction(function () {
            $user = User::activeAccount();
            $order = $this->orderId === null ? null : $this->editableOrder(lock: true);
            $data = $this->validate([
                'customer_name' => ['required', 'string', 'max:100'],
                'customer_phone' => ['required', 'string', 'max:40'],
                'category_id' => ['required', 'integer', 'min:1'],
                'complaint' => ['required', 'string', 'max:500'],
                'item_description' => ['nullable', 'string', 'max:100'],
                'customer_address' => ['nullable', 'string', 'max:255'],
            ], [], [
                'customer_name' => 'Nama customer', 'customer_phone' => 'Nomor HP', 'category_id' => 'Barang',
                'complaint' => 'Keluhan', 'item_description' => 'Keterangan barang', 'customer_address' => 'Alamat',
            ]);
            $category = Category::query()->whereKey($data['category_id'])->lockForUpdate()->first();
            if (! $category || (! $category->is_active && $category->id !== $order?->category_id)) {
                throw ValidationException::withMessages(['category_id' => 'Pilih kategori barang yang aktif.']);
            }
            $data['customer_phone'] = PhoneNumber::normalize($data['customer_phone']);
            if ($order === null) {
                return app(CreateServiceOrder::class)->create($data, $user);
            }
            $order->update($data);

            return $order;
        }, 5);
        $this->redirectRoute($this->orderId === null ? 'tts.success' : 'tts.show', ['id' => $order->id]);
    }

    public function render(): View
    {
        User::activeAccount();
        $order = $this->orderId === null ? null : $this->editableOrder();

        return view('livewire.tts.form', [
            'categories' => Category::query()->where('is_active', true)->orderBy('id')->get(['id', 'name']),
            'currentCategory' => $order?->category,
            'storeName' => StoreSetting::query()->value('name') ?: 'Anugerah',
        ])->layout('layouts.app', ['title' => $order === null ? 'TTS baru' : 'Ubah TTS']);
    }
}
