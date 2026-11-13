<?php

namespace App\Livewire\Invoice;

use App\Actions\ServiceOrders\InvoiceLines;
use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Form extends Component
{
    #[Locked]
    public int $orderId;

    #[Locked]
    public bool $correction;

    #[Locked]
    public string $revision;

    /** @var array<mixed> */
    public array $rows = [];

    public string $warranty_days = '30';

    public bool $unrepairable = false;

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    protected function order(): ServiceOrder
    {
        $order = ServiceOrder::query()->findOrFail($this->orderId);
        abort_unless(in_array($order->getAttribute('status'), [ServiceOrderStatus::Dikerjakan, ServiceOrderStatus::Selesai, ServiceOrderStatus::Diambil], true), 403);

        return $order;
    }

    public function mount(int $id): void
    {
        $this->orderId = $id;
        $order = $this->order();
        $this->correction = $order->getAttribute('status') !== ServiceOrderStatus::Dikerjakan;
        $this->revision = InvoiceLines::revision($order);
        $this->rows = $order->items()->orderBy('id')->get(['type', 'description', 'qty', 'price', 'cost'])->toArray();
        if ($this->rows === []) {
            $this->addRow();
        }
        $this->warranty_days = (string) ($this->correction ? $order->warranty_days : (StoreSetting::query()->value('default_warranty_days') ?? 30));
        $this->unrepairable = $order->unrepairable;
    }

    public function addRow(): void
    {
        User::activeAccount(admin: true);
        $this->rows[] = ['type' => 'part', 'description' => '', 'qty' => 1, 'price' => 0, 'cost' => 0];
    }

    public function removeRow(mixed $index): void
    {
        User::activeAccount(admin: true);
        $data = validator(['index' => $index], ['index' => ['required', 'integer', 'min:0']])->validate();
        unset($this->rows[$data['index']]);
        $this->rows = array_values($this->rows);
        $this->resetValidation();
    }

    public function save(): void
    {
        app(ManageServiceOrder::class)->invoice($this->orderId, ['rows' => $this->rows, 'warranty_days' => $this->warranty_days, 'unrepairable' => $this->unrepairable], $this->correction, $this->revision);
        $this->redirectRoute('tts.show', ['id' => $this->orderId]);
    }

    public function render(): View
    {
        User::activeAccount(admin: true);
        $order = $this->order()->load(['category:id,name', 'technician:id,name']);
        try {
            $totals = InvoiceLines::calculate($this->rows);
        } catch (ValidationException) {
            $totals = null;
        }

        $warrantyUntil = null;
        try {
            $days = validator(['days' => $this->warranty_days], ['days' => ['required', 'integer', 'min:0', 'max:4294967295']])->validate()['days'];
            $warrantyUntil = ManageServiceOrder::warrantyUntil($this->correction ? $order->getAttribute('invoiced_on') : CarbonImmutable::today('Asia/Makassar'), (int) $days);
        } catch (ValidationException) {
            $warrantyUntil = null;
        }

        return view('livewire.invoice.form', compact('order', 'totals', 'warrantyUntil'))->layout('layouts.app', ['title' => 'Rincian & nota']);
    }
}
