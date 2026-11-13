<?php

namespace Tests\Feature\Expenses;

use App\Livewire\Expenses\Index;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpensesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        return $user;
    }

    public function test_admin_creates_edits_and_deletes_expenses(): void
    {
        $admin = $this->admin();
        Livewire::test(Index::class)->call('create')->set('spent_on', '2026-11-13')->set('category', 'bensin')->set('description', '  Isi bensin  ')->set('amount', '150000')->call('save')->assertHasNoErrors();
        $expense = Expense::firstOrFail();
        $this->assertSame('Isi bensin', $expense->description);
        $this->assertSame(150000, $expense->amount);
        $this->assertSame($admin->id, $expense->created_by);
        Livewire::test(Index::class)->call('edit', $expense->id)->set('amount', '175000')->call('save')->assertHasNoErrors();
        $this->assertSame(175000, $expense->fresh()->amount);
        Livewire::test(Index::class)->call('confirmDelete', $expense->id)->call('cancelDelete')->assertHasNoErrors();
        $this->assertDatabaseCount('expenses', 1);
        Livewire::test(Index::class)->call('confirmDelete', $expense->id)->call('delete')->assertHasNoErrors();
        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_validates_category_amount_and_date_range_total(): void
    {
        $admin = $this->admin();
        foreach (['0', '-1', '1.5', '4294967296'] as $amount) {
            Livewire::test(Index::class)->call('create')->set('spent_on', '2026-11-12')->set('category', 'wrong')->set('amount', $amount)->call('save')->assertHasErrors(['category', 'amount']);
        }
        Expense::create(['spent_on' => '2026-11-12', 'category' => 'konsumsi', 'description' => '', 'amount' => 20000, 'created_by' => $admin->id]);
        Expense::create(['spent_on' => '2026-11-11', 'category' => 'gaji', 'description' => '', 'amount' => 45000, 'created_by' => $admin->id]);
        Expense::create(['spent_on' => '2026-11-14', 'category' => 'lainnya', 'description' => '', 'amount' => 90000, 'created_by' => $admin->id]);
        Livewire::test(Index::class)->set('from', '2026-11-11')->set('to', '2026-11-13')->assertSee('Rp65.000')->assertDontSee('Rp90.000');
    }

    public function test_date_filter_includes_both_endpoints_and_totals_only_matching_days(): void
    {
        $admin = $this->admin();
        foreach ([['2026-11-12', 10000], ['2026-11-13', 20000], ['2026-11-14', 40000]] as [$date, $amount]) {
            Expense::create(['spent_on' => $date, 'category' => 'bensin', 'description' => '', 'amount' => $amount, 'created_by' => $admin->id]);
        }

        $component = Livewire::test(Index::class)
            ->set('from', '2026-11-12')
            ->set('to', '2026-11-13')
            ->assertSee('Rp10.000')
            ->assertSee('Rp20.000')
            ->assertSee('Rp30.000')
            ->assertDontSee('Rp40.000');
        $component->set('from', '2026-11-14')->assertSee('Sampai tanggal harus sama atau setelah Dari tanggal.')->assertDontSee('Rp30.000');
        $component->set('from', '')->assertSee('Dari tanggal wajib diisi.');
        $component->set('from', '2026-11-12')->assertSee('Rp30.000');
    }

    public function test_date_fields_reject_non_iso_and_out_of_mysql_range_while_accepting_boundaries(): void
    {
        $admin = $this->admin();
        foreach (['tomorrow', '2026-1-2', '0999-12-31', '10000-01-01'] as $date) {
            Livewire::test(Index::class)->call('create')->set('spent_on', $date)->set('category', 'bensin')->set('amount', '1')->call('save')->assertHasErrors('spent_on');
        }
        foreach ([['1000-01-01', '1000-01-01'], ['9999-12-31', '9999-12-31']] as [$from, $to]) {
            Expense::create(['spent_on' => $from, 'category' => 'bensin', 'description' => '', 'amount' => 1000, 'created_by' => $admin->id]);
            Livewire::test(Index::class)->set('from', $from)->set('to', $to)->assertSee('Rp1.000');
        }
        $component = Livewire::test(Index::class)->set('from', '10000-01-01');
        $this->assertTrue($component->viewData('filterErrors')->has('from'));
    }

    public function test_technician_and_demoted_admin_cannot_use_component(): void
    {
        $tech = User::factory()->create(['role' => 'teknisi']);
        $this->actingAs($tech);
        $this->get('/pengeluaran')->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $component = Livewire::test(Index::class);
        $admin->update(['role' => 'teknisi']);
        $component->call('create')->assertForbidden();
    }
}
