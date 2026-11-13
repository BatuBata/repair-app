<?php

namespace App\Livewire\Expenses;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Index extends Component
{
    #[Locked]
    public ?int $expenseId = null;

    #[Locked]
    public string $mode = 'list';

    #[Locked]
    public ?int $deleteId = null;

    public string $from;

    public string $to;

    public string $spent_on = '';

    public string $category = '';

    public string $description = '';

    public string $amount = '';

    public function mount(): void
    {
        User::activeAccount(admin: true);
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function boot(): void
    {
        User::activeAccount(admin: true);
    }

    public function create(): void
    {
        User::activeAccount(admin: true);
        $this->resetForm();
        $this->spent_on = now()->toDateString();
        $this->mode = 'create';
    }

    public function edit(mixed $id): void
    {
        User::activeAccount(admin: true);
        $expense = $this->findExpense($id);
        $this->resetForm();
        $this->expenseId = $expense->id;
        $this->spent_on = Carbon::parse((string) $expense->getRawOriginal('spent_on'))->toDateString();
        $this->category = ExpenseCategory::from((string) $expense->getRawOriginal('category'))->value;
        $this->description = $expense->description ?? '';
        $this->amount = (string) $expense->amount;
        $this->mode = 'edit';
    }

    public function cancel(): void
    {
        User::activeAccount(admin: true);
        $this->resetForm();
    }

    public function save(): void
    {
        User::activeAccount(admin: true);
        abort_unless(in_array($this->mode, ['create', 'edit'], true), 422);
        $this->description = trim($this->description);
        $data = $this->validate([
            'spent_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
            'category' => ['required', Rule::enum(ExpenseCategory::class)],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ], [
            'spent_on.required' => 'Tanggal pengeluaran wajib diisi.',
            'spent_on.date_format' => 'Tanggal pengeluaran harus menggunakan format YYYY-MM-DD.',
            'spent_on.after_or_equal' => 'Tanggal pengeluaran terlalu awal.',
            'spent_on.before_or_equal' => 'Tanggal pengeluaran terlalu akhir.',
            'category.required' => 'Kategori wajib dipilih.', 'category.enum' => 'Kategori pengeluaran tidak valid.',
            'description.string' => 'Keterangan harus berupa teks.', 'description.max' => 'Keterangan maksimal 255 karakter.',
            'amount.required' => 'Nominal wajib diisi.', 'amount.integer' => 'Nominal harus berupa bilangan bulat.',
            'amount.min' => 'Nominal harus lebih dari 0.', 'amount.max' => 'Nominal melebihi batas penyimpanan.',
        ]);
        DB::transaction(function () use ($data): void {
            $admin = User::activeAccount(admin: true);
            if ($this->mode === 'create') {
                Expense::create([...$data, 'created_by' => $admin->id]);
            } else {
                $this->findExpense($this->expenseId)->update($data);
            }
        });
        $this->resetForm();
        session()->flash('expense-status', 'Pengeluaran berhasil disimpan.');
    }

    public function confirmDelete(mixed $id): void
    {
        User::activeAccount(admin: true);
        $this->deleteId = $this->findExpense($id)->id;
    }

    public function cancelDelete(): void
    {
        User::activeAccount(admin: true);
        $this->deleteId = null;
    }

    public function delete(): void
    {
        User::activeAccount(admin: true);
        abort_unless($this->deleteId !== null, 422);
        DB::transaction(function (): void {
            User::activeAccount(admin: true);
            $this->findExpense($this->deleteId)->delete();
        });
        $this->deleteId = null;
        session()->flash('expense-status', 'Pengeluaran berhasil dihapus.');
    }

    private function findExpense(mixed $id): Expense
    {
        $valid = Validator::make(['id' => $id], ['id' => ['required', 'integer', 'min:1']])->validate();

        return Expense::query()->whereKey($valid['id'])->firstOrFail();
    }

    private function resetForm(): void
    {
        $this->reset(['expenseId', 'spent_on', 'category', 'description', 'amount']);
        $this->resetValidation();
        $this->mode = 'list';
    }

    public function render(): View
    {
        User::activeAccount(admin: true);
        $filters = Validator::make(['from' => $this->from, 'to' => $this->to], [
            'from' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from', 'before_or_equal:9999-12-31'],
        ], [
            'from.required' => 'Dari tanggal wajib diisi.',
            'from.date_format' => 'Dari tanggal harus menggunakan format YYYY-MM-DD.',
            'from.after_or_equal' => 'Dari tanggal terlalu awal.',
            'from.before_or_equal' => 'Dari tanggal terlalu akhir.',
            'to.required' => 'Sampai tanggal wajib diisi.',
            'to.date_format' => 'Sampai tanggal harus menggunakan format YYYY-MM-DD.',
            'to.after_or_equal' => 'Sampai tanggal harus sama atau setelah Dari tanggal.',
            'to.before_or_equal' => 'Sampai tanggal terlalu akhir.',
        ], ['from' => 'Dari tanggal', 'to' => 'Sampai tanggal']);
        $query = Expense::query();
        if ($filters->fails()) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereDate('spent_on', '>=', $this->from)->whereDate('spent_on', '<=', $this->to);
        }

        return view('livewire.expenses.index', [
            'expenses' => (clone $query)->orderByDesc('spent_on')->orderByDesc('id')->get(),
            'total' => (clone $query)->sum('amount'),
            'filterErrors' => $filters->errors(),
            'categories' => ExpenseCategory::cases(),
            'deleteExpense' => $this->deleteId ? $this->findExpense($this->deleteId) : null,
        ])->layout('layouts.app', ['title' => 'Pengeluaran']);
    }
}
