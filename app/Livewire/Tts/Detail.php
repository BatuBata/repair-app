<?php

namespace App\Livewire\Tts;

use App\Actions\ServiceOrders\InvoiceLines;
use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Enums\ServiceOrderStatus;
use App\Enums\UserRole;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\TenantContext;
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

    public string $assignment_stage = 'pengecekan';

    public string $inspection_result = '';

    public string $action_plan = '';

    public string $picked_up_on = '';

    public string $cancel_reason = '';

    public string $payment_method = 'cash';

    public string $payment_bank = '';

    public function boot(): void
    {
        User::activeAccount();
    }

    public function mount(int $id): void
    {
        $this->orderId = $id;
        $this->success = request()->route()?->getName() === 'tts.success';
        $order = ServiceOrder::query()->findOrFail($id);
        $this->technician_id = (string) $order->technician_id;
        $this->inspection_result = $order->inspection_result ?? '';
        $this->action_plan = $order->action_plan ?? '';
        $this->payment_method = $order->payment_method ?: 'cash';
        $this->payment_bank = $order->payment_bank ?: '';
        $this->picked_up_on = now('Asia/Makassar')->toDateString();
    }

    public function assign(): void
    {
        app(ManageServiceOrder::class)->assign($this->orderId, $this->technician_id, $this->assignment_stage);
        session()->flash('tts-status', 'Teknisi berhasil disimpan.');
        $this->redirectRoute('tts.show', ['id' => $this->orderId]);
    }

    public function saveInspection(): void
    {
        $this->resetValidation();
        app(ManageServiceOrder::class)->saveInspection($this->orderId, $this->only(['inspection_result', 'action_plan']));
        session()->flash('tts-status', 'Hasil pengecekan berhasil disimpan.');
        $this->redirectRoute('tts.show', ['id' => $this->orderId]);
    }

    public function startWork(): void
    {
        app(ManageServiceOrder::class)->startWork($this->orderId);
        session()->flash('tts-status', 'Pengerjaan dimulai.');
        $this->redirectRoute('tts.show', ['id' => $this->orderId]);
    }

    public function sendInspection(): void
    {
        $this->resetValidation();
        app(ManageServiceOrder::class)->saveInspection($this->orderId, $this->only(['inspection_result', 'action_plan']));
        $this->redirectRoute('tts.whatsapp', ['id' => $this->orderId, 'inspection' => 1]);
    }

    public function collect(): void
    {
        app(ManageServiceOrder::class)->collect($this->orderId, $this->picked_up_on, $this->only(['payment_method', 'payment_bank']));
        session()->flash('tts-status', 'Barang sudah diambil.');
        $this->redirectRoute('tts.show', ['id' => $this->orderId]);
    }

    public function recordPayment(): void
    {
        app(ManageServiceOrder::class)->recordPayment($this->orderId, $this->only(['payment_method', 'payment_bank']));
        session()->flash('tts-status', 'Pembayaran berhasil disimpan.');
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
            ->select(['id', 'number', 'customer_name', 'customer_phone', 'customer_address', 'category_id', 'item_description', 'complaint', 'status', 'created_by', 'technician_id', 'created_at', 'assigned_at', 'inspection_started_at', 'work_started_at', 'inspection_result', 'action_plan', 'invoiced_on', 'picked_up_on', 'cancelled_at', 'cancel_reason', 'warranty_days', 'warranty_until', 'unrepairable', 'discount', 'payment_method', 'payment_bank', 'paid_at'])
            ->with(['category:id,name', 'creator:id,name', 'technician:id,name'])->findOrFail($this->orderId);
        $canEdit = $user->role === UserRole::Admin || ($order->created_by === $user->id && $order->getAttribute('status') === ServiceOrderStatus::Diterima);

        $isAdmin = $user->role === UserRole::Admin;
        $financial = null;
        if ($isAdmin && $order->invoiced_on) {
            try {
                $financial = InvoiceLines::calculate($order->items()->orderBy('id')->get(['type', 'description', 'qty', 'price', 'cost'])->toArray(), $order->discount ?? 0);
            } catch (ValidationException) {
                $financial = null;
            }
        }

        return view('livewire.tts.detail', [
            'order' => $order, 'canEdit' => $canEdit, 'user' => $user, 'isAdmin' => $isAdmin, 'financial' => $financial,
            'technicians' => $isAdmin ? User::query()->where('role', UserRole::Teknisi)->where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect(),
            'storeName' => StoreSetting::query()->value('name') ?: app(TenantContext::class)->tenant()->name,
        ])->layout('layouts.app', ['title' => $order->number, 'navigationPath' => $this->success ? '/tts/baru' : null]);
    }
}
