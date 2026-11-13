<?php

namespace App\Livewire\Tts;

use App\Enums\ServiceOrderStatus;
use App\Enums\UserRole;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Detail extends Component
{
    #[Locked]
    public int $orderId;

    #[Locked]
    public bool $success = false;

    public function boot(): void
    {
        User::activeAccount();
    }

    public function mount(int $id): void
    {
        $this->orderId = $id;
        $this->success = request()->route()?->getName() === 'tts.success';
    }

    public function render(): View
    {
        $user = User::activeAccount();
        $order = ServiceOrder::query()
            ->select(['id', 'number', 'customer_name', 'customer_phone', 'customer_address', 'category_id', 'item_description', 'complaint', 'status', 'created_by', 'technician_id', 'created_at', 'assigned_at', 'invoiced_on', 'picked_up_on', 'cancelled_at'])
            ->with(['category:id,name', 'creator:id,name', 'technician:id,name'])->findOrFail($this->orderId);
        $canEdit = $user->role === UserRole::Admin || ($order->created_by === $user->id && $order->getAttribute('status') === ServiceOrderStatus::Diterima);

        return view('livewire.tts.detail', [
            'order' => $order, 'canEdit' => $canEdit, 'user' => $user,
            'storeName' => StoreSetting::query()->value('name') ?: 'Anugerah',
        ])->layout('layouts.app', ['title' => $order->number, 'navigationPath' => $this->success ? '/tts/baru' : null]);
    }
}
