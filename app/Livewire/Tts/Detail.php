<?php

namespace App\Livewire\Tts;

use App\Actions\ServiceOrders\InvoiceLines;
use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Enums\ServiceOrderStatus;
use App\Enums\UserRole;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Detail extends Component
{
    #[Locked]
    public int $orderId;

    #[Locked]
    public bool $success = false;

    public string $technician_id = '';

    public string $picked_up_on = '';

    public string $cancel_reason = '';

    public function boot(): void
    {
        User::activeAccount();
    }

    public function mount(int $id): void
    {
        $this->orderId = $id;
        $this->success = request()->route()?->getName() === 'tts.success';
        $this->technician_id = (string) ServiceOrder::query()->findOrFail($id)->technician_id;
        $this->picked_up_on = now('Asia/Makassar')->toDateString();
    }

    public function assign(): void
    {
        app(ManageServiceOrder::class)->assign($this->orderId, $this->technician_id);
        session()->flash('tts-status', 'Teknisi berhasil disimpan.');
        $this->redirectRoute('tts.show', ['id' => $this->orderId]);
    }

    public function collect(): void
    {
        app(ManageServiceOrder::class)->collect($this->orderId, $this->picked_up_on);
        $this->redirectRoute('tts.show', ['id' => $this->orderId]);
    }

    public function cancel(): void
    {
        app(ManageServiceOrder::class)->cancel($this->orderId, $this->cancel_reason);
        $this->redirectRoute('tts.show', ['id' => $this->orderId]);
    }

    public function render(): View
    {
        $user = User::activeAccount();
        $order = ServiceOrder::query()
            ->select(['id', 'number', 'customer_name', 'customer_phone', 'customer_address', 'category_id', 'item_description', 'complaint', 'status', 'created_by', 'technician_id', 'created_at', 'assigned_at', 'invoiced_on', 'picked_up_on', 'cancelled_at', 'cancel_reason', 'warranty_days', 'warranty_until', 'unrepairable'])
            ->with(['category:id,name', 'creator:id,name', 'technician:id,name'])->findOrFail($this->orderId);
        $canEdit = $user->role === UserRole::Admin || ($order->created_by === $user->id && $order->getAttribute('status') === ServiceOrderStatus::Diterima);

        $isAdmin = $user->role === UserRole::Admin;
        $financial = null;
        if ($isAdmin && $order->invoiced_on) {
            try {
                $financial = InvoiceLines::calculate($order->items()->orderBy('id')->get(['type', 'description', 'qty', 'price', 'cost'])->toArray());
            } catch (ValidationException) {
                $financial = null;
            }
        }

        return view('livewire.tts.detail', [
            'order' => $order, 'canEdit' => $canEdit, 'user' => $user, 'isAdmin' => $isAdmin, 'financial' => $financial,
            'technicians' => $isAdmin ? User::query()->where('role', UserRole::Teknisi)->where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'storeName' => StoreSetting::query()->value('name') ?: 'Anugerah',
        ])->layout('layouts.app', ['title' => $order->number, 'navigationPath' => $this->success ? '/tts/baru' : null]);
    }
}
