<?php

namespace App\Livewire\Settings;

use App\Models\Bank;
use App\Models\User;
use App\Support\Payment;
use App\Support\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Banks extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $bankId = null;

    #[Locked]
    public bool $editing = false;

    public string $name = '';

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function create(): void
    {
        $this->cancel();
        $this->editing = true;
    }

    public function edit(int $id): void
    {
        User::activeAccount(admin: true);
        $bank = $this->customBank($id);
        $this->resetValidation();
        $this->bankId = $bank->id;
        $this->name = $bank->name;
        $this->editing = true;
    }

    public function cancel(): void
    {
        User::activeAccount(admin: true);
        $this->reset(['bankId', 'editing', 'name']);
        $this->resetValidation();
    }

    public function save(): void
    {
        User::activeAccount(admin: true);
        abort_unless($this->editing, 422);
        $this->name = trim($this->name);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('banks', 'name')->where('tenant_id', app(TenantContext::class)->id())->ignore($this->bankId),
                function ($attribute, $value, $fail) {
                    if (in_array(mb_strtolower($value), array_map('mb_strtolower', [...Payment::BANKS, 'semua']), true)) {
                        $fail('Nama tersebut sudah digunakan oleh daftar bank bawaan.');
                    }
                }],
        ], ['name.required' => 'Nama bank wajib diisi.', 'name.max' => 'Nama bank maksimal 50 karakter.', 'name.unique' => 'Nama bank sudah digunakan di toko ini.']);
        $bank = $this->bankId === null ? new Bank : $this->customBank($this->bankId);
        $bank->fill($data)->save();
        $this->cancel();
        $this->resetPage();
        session()->flash('bank_status', 'Bank berhasil disimpan.');
    }

    public function toggleDefault(string $name): void
    {
        User::activeAccount(admin: true);
        validator(['name' => $name], ['name' => ['required', Rule::in(Payment::BANKS)]])->validate();
        $bank = Bank::firstOrCreate(['name' => $name]);
        $bank->update(['is_active' => ! $bank->is_active]);
        session()->flash('bank_status', 'Status bank di toko ini berhasil diubah.');
    }

    public function toggle(int $id): void
    {
        User::activeAccount(admin: true);
        $bank = $this->customBank($id);
        $bank->update(['is_active' => ! $bank->is_active]);
        session()->flash('bank_status', 'Status bank di toko ini berhasil diubah.');
    }

    public function delete(int $id): void
    {
        User::activeAccount(admin: true);
        $this->customBank($id)->delete();
        $this->resetPage();
        session()->flash('bank_status', 'Bank tambahan dihapus. Pembayaran lama tetap tersimpan.');
    }

    private function customBank(int $id): Bank
    {
        $bank = Bank::findOrFail($id);
        abort_if($bank->isDefault(), 403);

        return $bank;
    }

    public function render(): View
    {
        User::activeAccount(admin: true);
        $configured = Bank::query()->orderBy('name')->get();
        $defaults = collect(Payment::BANKS)->map(fn ($name) => ['id' => null, 'name' => $name, 'is_default' => true, 'is_active' => $configured->firstWhere('name', $name)->is_active ?? true]);
        $custom = $configured->reject(fn ($bank) => $bank->isDefault())->map(fn ($bank) => ['id' => $bank->id, 'name' => $bank->name, 'is_default' => false, 'is_active' => $bank->is_active]);

        $rows = $defaults->concat($custom)->filter(fn ($row) => str_contains(mb_strtolower($row['name']), mb_strtolower(trim($this->search))))->values();
        $page = $this->getPage();
        $banks = new LengthAwarePaginator($rows->forPage($page, 20)->values(), $rows->count(), 20, $page);

        return view('livewire.settings.banks', compact('banks'))
            ->layout('layouts.app', ['title' => 'Bank']);
    }
}
