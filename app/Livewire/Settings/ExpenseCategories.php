<?php

namespace App\Livewire\Settings;

use App\Models\ExpenseCategory;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseCategories extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $categoryId = null;

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
        $category = $this->customExpenseCategory($id);
        $this->resetValidation();
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->editing = true;
    }

    public function cancel(): void
    {
        User::activeAccount(admin: true);
        $this->reset(['categoryId', 'editing', 'name']);
        $this->resetValidation();
    }

    public function save(): void
    {
        User::activeAccount(admin: true);
        abort_unless($this->editing, 422);
        $this->name = trim($this->name);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('expense_categories', 'name')->where('tenant_id', app(TenantContext::class)->id())->ignore($this->categoryId),
                function ($attribute, $value, $fail) {
                    if (in_array(mb_strtolower($value), ExpenseCategory::defaultNames(), true)) {
                        $fail('Nama tersebut sudah digunakan oleh daftar kategori pengeluaran bawaan.');
                    }
                }],
        ], ['name.required' => 'Nama kategori wajib diisi.', 'name.max' => 'Nama kategori maksimal 50 karakter.', 'name.unique' => 'Nama kategori sudah digunakan di toko ini.']);
        $category = $this->categoryId === null ? new ExpenseCategory : $this->customExpenseCategory($this->categoryId);
        $category->fill($data)->save();
        $this->cancel();
        $this->resetPage();
        session()->flash('expense_category_status', 'Kategori berhasil disimpan.');
    }

    public function toggleDefault(string $name): void
    {
        User::activeAccount(admin: true);
        validator(['name' => $name], ['name' => ['required', Rule::in(ExpenseCategory::defaultNames())]])->validate();
        $category = ExpenseCategory::firstOrCreate(['name' => $name]);
        $category->update(['is_active' => ! $category->is_active]);
        session()->flash('expense_category_status', 'Status kategori di toko ini berhasil diubah.');
    }

    public function toggle(int $id): void
    {
        User::activeAccount(admin: true);
        $category = $this->customExpenseCategory($id);
        $category->update(['is_active' => ! $category->is_active]);
        session()->flash('expense_category_status', 'Status kategori di toko ini berhasil diubah.');
    }

    public function delete(int $id): void
    {
        User::activeAccount(admin: true);
        $this->customExpenseCategory($id)->delete();
        $this->resetPage();
        session()->flash('expense_category_status', 'Kategori tambahan dihapus. Pengeluaran lama tetap tersimpan.');
    }

    private function customExpenseCategory(int $id): ExpenseCategory
    {
        $category = ExpenseCategory::findOrFail($id);
        abort_if($category->isDefault(), 403);

        return $category;
    }

    public function render(): View
    {
        User::activeAccount(admin: true);
        $configured = ExpenseCategory::query()->orderBy('name')->get();
        $defaults = collect(ExpenseCategory::defaultNames())->map(fn ($name) => ['id' => null, 'name' => $name, 'is_default' => true, 'is_active' => $configured->firstWhere('name', $name)->is_active ?? true]);
        $custom = $configured->reject(fn ($category) => $category->isDefault())->map(fn ($category) => ['id' => $category->id, 'name' => $category->name, 'is_default' => false, 'is_active' => $category->is_active]);

        $rows = $defaults->concat($custom)->filter(fn ($row) => str_contains(mb_strtolower($row['name']), mb_strtolower(trim($this->search))))->values();
        $page = $this->getPage();
        $categories = new LengthAwarePaginator($rows->forPage($page, 20)->values(), $rows->count(), 20, $page);

        return view('livewire.settings.expense-categories', compact('categories'))
            ->layout('layouts.app', ['title' => 'Kategori pengeluaran']);
    }
}
