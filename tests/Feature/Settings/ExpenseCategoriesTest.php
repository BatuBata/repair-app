<?php

namespace Tests\Feature\Settings;

use App\Livewire\Expenses\Index as Expenses;
use App\Livewire\Settings\ExpenseCategories;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Reports\Report;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ExpenseCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        return $user;
    }

    public function test_defaults_only_toggle_and_are_configured_separately_per_store(): void
    {
        $user = $this->admin();
        $this->assertSame(['bensin', 'konsumsi', 'gaji', 'lainnya'], ExpenseCategory::activeNames());
        Livewire::test(ExpenseCategories::class)->call('toggleDefault', 'bensin')->assertHasNoErrors();
        $this->assertNotContains('bensin', ExpenseCategory::activeNames());
        $default = ExpenseCategory::firstOrFail();
        foreach (['edit', 'toggle', 'delete'] as $action) {
            Livewire::test(ExpenseCategories::class)->call($action, $default->id)->assertForbidden();
        }
        $other = Tenant::create(['name' => 'Toko B']);
        app(TenantContext::class)->set($other);
        $this->assertContains('bensin', ExpenseCategory::activeNames());
        app(TenantContext::class)->set(Tenant::findOrFail($user->tenant_id));
        Livewire::test(ExpenseCategories::class)->call('toggleDefault', 'bensin')->assertHasNoErrors();
        $this->assertContains('bensin', ExpenseCategory::activeNames());
        Livewire::test(ExpenseCategories::class)->call('toggleDefault', 'bukan-bawaan')->assertHasErrors('name');
    }

    public function test_custom_crud_search_toggle_and_case_insensitive_default_protection(): void
    {
        $this->admin();
        $form = Livewire::test(ExpenseCategories::class)->call('create');
        foreach (['', ' BENSIN ', 'Konsumsi', 'GAJI', 'Lainnya', str_repeat('x', 51)] as $name) {
            $form->set('name', $name)->call('save')->assertHasErrors('name');
        }
        $form->set('name', ' Listrik ')->call('save')->assertHasNoErrors();
        $category = ExpenseCategory::firstOrFail();
        $this->assertSame('Listrik', $category->name);
        Livewire::test(ExpenseCategories::class)->set('search', 'lIsTr')->assertSee('Listrik')->assertDontSee('bensin');
        Livewire::test(ExpenseCategories::class)->set('search', 'tidak ada')->assertSee('Kategori tidak ditemukan.');
        Livewire::test(ExpenseCategories::class)->call('create')->set('name', 'Listrik')->call('save')->assertHasErrors('name');
        Livewire::test(ExpenseCategories::class)->call('edit', $category->id)->set('name', 'Air dan listrik')->call('save')->assertHasNoErrors();
        $this->assertSame('Air dan listrik', $category->fresh()->name);
        Livewire::test(ExpenseCategories::class)->call('toggle', $category->id)->assertHasNoErrors();
        $this->assertNotContains('Air dan listrik', ExpenseCategory::activeNames());
        Livewire::test(ExpenseCategories::class)->call('delete', $category->id)->assertHasNoErrors();
        $this->assertDatabaseCount('expense_categories', 0);
        $this->get('/pengaturan/kategori-pengeluaran')->assertOk()->assertSee('Kategori pengeluaran');
        $this->get('/pengaturan/bank')->assertOk()->assertSee('/pengaturan/kategori-pengeluaran', false);
    }

    public function test_default_model_cannot_be_renamed_or_deleted_through_an_unsaved_name(): void
    {
        $this->admin();
        $category = ExpenseCategory::create(['name' => 'BENSIN']);
        $this->assertSame('bensin', $category->name);
        foreach (['save', 'delete'] as $action) {
            $category->name = 'Nama lain';
            try {
                $category->$action();
                $this->fail('Kategori bawaan harus tetap dilindungi.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
        $this->assertDatabaseHas('expense_categories', ['id' => $category->id, 'name' => 'bensin']);
    }

    public function test_admin_guards_and_foreign_ids_apply_to_all_category_actions(): void
    {
        $this->get('/pengaturan/kategori-pengeluaran')->assertRedirect('/login');
        $user = $this->admin();
        $local = ExpenseCategory::create(['name' => 'Listrik']);
        $other = Tenant::create(['name' => 'Toko B']);
        app(TenantContext::class)->set($other);
        $foreign = ExpenseCategory::create(['name' => 'Biaya privat B']);
        app(TenantContext::class)->set(Tenant::findOrFail($user->tenant_id));
        foreach (['edit', 'toggle', 'delete'] as $action) {
            Livewire::test(ExpenseCategories::class)->assertDontSee('Biaya privat B')->call($action, $foreign->id)->assertNotFound();
        }
        Livewire::test(ExpenseCategories::class)->call('create')->set('name', 'Biaya privat B')->call('save')->assertHasNoErrors();
        Livewire::test(Expenses::class)->call('create')->set('spent_on', '2026-11-19')->set('category', 'Tidak milik toko ini')->set('amount', '1')->call('save')->assertHasErrors('category');
        $editor = Livewire::test(ExpenseCategories::class)->call('edit', $local->id)->set('name', 'Tidak boleh');
        User::whereKey($user->id)->update(['role' => 'teknisi']);
        $editor->call('save')->assertForbidden();
        $this->get('/pengaturan/kategori-pengeluaran')->assertForbidden();
        $this->assertDatabaseHas('expense_categories', ['id' => $local->id, 'name' => 'Listrik']);
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_new_expenses_reject_inactive_or_foreign_categories_at_save_and_model_boundary(): void
    {
        $user = $this->admin();
        $category = ExpenseCategory::create(['name' => 'Listrik']);
        $stale = Livewire::test(Expenses::class)->call('create')->set('spent_on', '2026-11-19')->set('category', 'Listrik')->set('amount', '100');
        Livewire::test(ExpenseCategories::class)->call('toggle', $category->id);
        $stale->call('save')->assertHasErrors('category');
        Livewire::test(ExpenseCategories::class)->call('toggleDefault', 'bensin');
        foreach (['Listrik', 'bensin', 'Kategori asing'] as $name) {
            try {
                Expense::create(['spent_on' => '2026-11-19', 'category' => $name, 'amount' => 100, 'created_by' => $user->id]);
                $this->fail('Kategori tidak aktif/asing harus ditolak.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('category', $exception->errors());
            }
        }
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_snapshots_and_edits_survive_inactive_renamed_or_deleted_categories_and_reports_stay_readable(): void
    {
        $user = $this->admin();
        $category = ExpenseCategory::create(['name' => 'Listrik, air']);
        Livewire::test(Expenses::class)->call('create')->set('spent_on', '2026-11-19')->set('category', $category->name)->set('amount', '15000')->call('save')->assertHasNoErrors();
        $expense = Expense::firstOrFail();
        Livewire::test(ExpenseCategories::class)->call('toggle', $category->id);
        Livewire::test(Expenses::class)->call('edit', $expense->id)->set('amount', '20000')->call('save')->assertHasNoErrors();
        Livewire::test(ExpenseCategories::class)->call('edit', $category->id)->set('name', 'Utilitas')->call('save')->assertHasNoErrors();
        $this->assertSame('Listrik, air', $expense->fresh()->category);
        Livewire::test(ExpenseCategories::class)->call('delete', $category->id)->assertHasNoErrors();
        Livewire::test(Expenses::class)->call('edit', $expense->id)->assertSet('category', 'Listrik, air')->set('description', 'Koreksi keterangan')->call('save')->assertHasNoErrors();
        Livewire::test(Expenses::class)->call('edit', $expense->id)->set('category', 'Kategori palsu')->call('save')->assertHasErrors('category');
        Livewire::test(Expenses::class)->call('create')->set('spent_on', '2026-11-19')->set('category', 'Listrik, air')->set('amount', '1')->call('save')->assertHasErrors('category');
        $this->assertSame('Listrik, air', $expense->fresh()->category);
        $report = app(Report::class)->build('2026-11-19', '2026-11-19');
        $this->assertSame('20000', $report['expenses']);
        $this->assertSame('Listrik, air', $report['rows'][0]['number']);
        $this->assertSame($user->id, $expense->fresh()->created_by);
    }
}
