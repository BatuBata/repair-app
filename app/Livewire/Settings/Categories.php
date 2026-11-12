<?php

namespace App\Livewire\Settings;

use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Categories extends Component
{
    #[Locked]
    public ?int $categoryId = null;

    #[Locked]
    public string $mode = 'list';

    public string $name = '';

    public string $status = '';

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function create(): void
    {
        User::activeAccount(admin: true);
        $this->reset(['categoryId', 'mode', 'name']);
        $this->resetValidation();
        $this->status = '';
        $this->mode = 'create';
    }

    public function edit(mixed $id): void
    {
        User::activeAccount(admin: true);
        $category = $this->findCategory($id);
        $this->reset(['categoryId', 'mode', 'name']);
        $this->resetValidation();
        $this->status = '';
        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->mode = 'edit';
    }

    public function cancel(): void
    {
        User::activeAccount(admin: true);
        $this->reset(['categoryId', 'mode', 'name']);
        $this->resetValidation();
        $this->status = '';
    }

    public function save(): void
    {
        User::activeAccount(admin: true);
        abort_unless(in_array($this->mode, ['create', 'edit'], true), 422);
        $this->name = trim($this->name);
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($this->categoryId)],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.string' => 'Nama kategori harus berupa teks.',
            'name.max' => 'Nama kategori maksimal 255 karakter.',
            'name.unique' => 'Nama kategori sudah digunakan.',
        ]);

        if ($this->mode === 'create') {
            Category::create($data);
        } else {
            $this->findCategory($this->categoryId)->update($data);
        }
        $this->reset(['categoryId', 'mode', 'name']);
        $this->status = 'Kategori berhasil disimpan.';
    }

    public function toggleActive(mixed $id): void
    {
        User::activeAccount(admin: true);
        $category = $this->findCategory($id);
        $category->forceFill(['is_active' => ! $category->is_active])->save();
        $this->status = 'Status kategori berhasil diubah.';
    }

    protected function findCategory(mixed $id): Category
    {
        $validated = Validator::make(['id' => $id], ['id' => ['required', 'integer', 'min:1']])->validate();

        return Category::query()->whereKey($validated['id'])->firstOrFail();
    }

    public function render(): View
    {
        User::activeAccount(admin: true);

        return view('livewire.settings.categories', ['categories' => Category::query()->orderBy('name')->get()])
            ->layout('layouts.app', ['title' => 'Kategori']);
    }
}
