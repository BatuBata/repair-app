<?php

namespace App\Livewire\Tts;

use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = '';

    public function boot(): void
    {
        User::activeAccount();
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['q', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        $user = User::activeAccount();
        $this->validate(['q' => ['string', 'max:100'], 'status' => ['nullable', Rule::enum(ServiceOrderStatus::class)]]);
        $search = trim($this->q);
        $phone = preg_replace('/[\s+\-]/u', '', $search) ?? '';
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }
        $orders = ServiceOrder::query()
            ->select(['id', 'number', 'customer_name', 'customer_phone', 'category_id', 'item_description', 'complaint', 'status', 'created_by', 'technician_id', 'created_at'])
            ->with(['category:id,name', 'technician:id,name'])
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('number', 'like', '%'.$search.'%')->orWhere('customer_name', 'like', '%'.$search.'%')->orWhere('customer_phone', 'like', '%'.$phone.'%')))
            ->orderByDesc('id')->paginate(30);

        return view('livewire.tts.index', compact('orders', 'user'))->layout('layouts.app', ['title' => 'Servis']);
    }
}
