<?php

namespace Tests\Feature;

use App\Livewire\Settings\Banks;
use App\Livewire\Settings\Categories;
use App\Livewire\Settings\ExpenseCategories;
use App\Livewire\Tts\Index;
use App\Models\Bank;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Payment;
use App\Support\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use Tests\TestCase;

class IndexPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(Tenant $tenant, int $count): User
    {
        app(TenantContext::class)->set($tenant);
        $admin = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'name' => 'Operator']);
        for ($i = 1; $i <= $count; $i++) {
            $name = sprintf('Listing %03d', $i);
            User::factory()->create(['tenant_id' => $tenant->id, 'name' => $name, 'username' => 'listing-'.$tenant->id.'-'.$i]);
            Customer::create(['name' => $name, 'phone' => '628120000'.sprintf('%04d', $i)]);
            $category = Category::create(['name' => $name]);
            Bank::create(['name' => $name]);
            ExpenseCategory::create(['name' => $name]);
            $product = Product::create(['name' => $name, 'stock' => 10, 'cost_price' => 400, 'sell_price' => 1000]);
            $sale = Sale::create(['number' => sprintf('JL-INDEX-%03d', $i), 'sold_on' => '2026-11-19', 'customer_name' => $name, 'customer_phone' => '081200000001', 'payment_method' => 'cash', 'created_by' => $admin->id]);
            $sale->items()->create(['product_id' => $product->id, 'product_name' => $name, 'qty' => 1, 'price' => 1000, 'cost' => 400]);
            Expense::create(['spent_on' => '2026-11-19', 'category' => 'bensin', 'description' => $name, 'amount' => 100, 'created_by' => $admin->id]);
            ServiceOrder::create(['number' => sprintf('TTS-INDEX-%03d', $i), 'customer_name' => $name, 'customer_phone' => '081200000001', 'category_id' => $category->id, 'complaint' => 'Mati', 'created_by' => $admin->id]);
        }

        return $admin;
    }

    private function localFixtures(): User
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-19 10:00:00', 'Asia/Makassar'));
        $tenant = Tenant::findOrFail(1);
        $admin = $this->fixture($tenant, 25);
        $this->fixture(Tenant::create(['name' => 'Foreign store']), 1);
        app(TenantContext::class)->set($tenant);
        $this->actingAs($admin);

        return $admin;
    }

    public function test_all_store_indexes_paginate_search_reset_and_keep_tenant_scope(): void
    {
        $this->localFixtures();
        $indexes = [
            [Index::class, 'orders', 'q'],
            [\App\Livewire\Customers\Index::class, 'customers', 'search'],
            [\App\Livewire\Sales\Index::class, 'sales', 'search'],
            [\App\Livewire\Stock\Index::class, 'products', 'search'],
            [\App\Livewire\Expenses\Index::class, 'expenses', 'search'],
            [\App\Livewire\Users\Index::class, 'users', 'search'],
            [Categories::class, 'categories', 'search'],
            [Banks::class, 'banks', 'search'],
            [ExpenseCategories::class, 'categories', 'search'],
        ];
        foreach ($indexes as [$component, $rows, $search]) {
            $view = Livewire::test($component)->set($search, 'Listing');
            $view->assertViewHas($rows, fn ($page) => $page instanceof LengthAwarePaginator && $page->total() === 25 && $page->perPage() === 20 && count($page) === 20)
                ->assertSee('Navigasi halaman tabel')->assertSee('index-toolbar');
            $firstIds = collect($view->viewData($rows)->items())->pluck('id');
            $view->call('nextPage')->assertViewHas($rows, fn ($page) => $page->currentPage() === 2 && count($page) === 5);
            $this->assertEmpty($firstIds->intersect(collect($view->viewData($rows)->items())->pluck('id'))->all(), $component);
            $view->set($search, 'Listing 025')->assertViewHas($rows, fn ($page) => $page->currentPage() === 1 && $page->total() === 1);
            $view->set($search, 'not-found')->assertViewHas($rows, fn ($page) => $page->total() === 0 && count($page) === 0)->assertSee('0–0');
            $view->set($search, 'Listing')->assertViewHas($rows, fn ($page) => $page->total() === 25 && count($page) === 20);
        }
    }

    public function test_virtual_defaults_stay_in_total_and_deleting_last_page_custom_row_resets_page(): void
    {
        $this->localFixtures();
        foreach ([[Banks::class, 'banks', Bank::class, count(Payment::BANKS)], [ExpenseCategories::class, 'categories', ExpenseCategory::class, count(ExpenseCategory::defaultNames())]] as [$component, $rows, $model, $defaults]) {
            $view = Livewire::test($component)->assertViewHas($rows, fn ($page) => $page->total() === 25 + $defaults);
            $view->set('search', 'Listing')->call('nextPage')->assertViewHas($rows, fn ($page) => $page->currentPage() === 2);
            $id = $model::where('name', 'Listing 025')->value('id');
            $view->call('delete', $id)->assertViewHas($rows, fn ($page) => $page->currentPage() === 1 && $page->total() === 24);
        }
        Livewire::test(Banks::class)->set('search', 'bca')->assertViewHas('banks', fn ($page) => $page->total() === 1 && $page->items()[0]['is_default']);
    }

    public function test_editing_a_searched_row_returns_to_first_page_when_it_leaves_results(): void
    {
        $this->localFixtures();
        foreach ([[\App\Livewire\Customers\Index::class, 'customers', Customer::class], [Banks::class, 'banks', Bank::class], [ExpenseCategories::class, 'categories', ExpenseCategory::class], [Categories::class, 'categories', Category::class], [\App\Livewire\Users\Index::class, 'users', User::class]] as [$component, $rows, $model]) {
            $id = $model::where('name', 'Listing 025')->value('id');
            $view = Livewire::test($component)->set('search', 'Listing')->call('edit', $id)->set('search', 'Listing')->call('gotoPage', 2)->set('name', 'Renamed');
            if ($model === User::class) {
                $view->set('username', 'renamed.qa');
            }
            $view->call('save')->assertHasNoErrors()
                ->assertViewHas($rows, fn ($page) => $page->currentPage() === 1 && $page->total() === 24);
        }
    }

    public function test_report_pagination_does_not_truncate_totals_and_search_keeps_financial_summary(): void
    {
        $this->localFixtures();
        $view = Livewire::test(\App\Livewire\Reports\Index::class);
        $view->assertViewHas('rows', fn ($page) => $page->total() === 50 && count($page) === 20)
            ->assertViewHas('report', fn ($report) => $report['revenue'] === '25000' && $report['net'] === '12500')
            ->assertViewHas('totals', fn ($totals) => $totals['profit'] === '12500');
        $view->call('nextPage')->assertViewHas('rows', fn ($page) => $page->currentPage() === 2 && count($page) === 20)
            ->assertViewHas('totals', fn ($totals) => $totals['profit'] === '12500');
        $view->set('search', 'JL-INDEX-025')->assertViewHas('rows', fn ($page) => $page->currentPage() === 1 && $page->total() === 1)
            ->assertViewHas('report', fn ($report) => $report['net'] === '12500')
            ->assertViewHas('totals', fn ($totals) => $totals['amount'] === '1000' && $totals['profit'] === '600');
        $view->set('search', '')->set('type', 'jual')->assertViewHas('rows', fn ($page) => $page->total() === 25)
            ->call('nextPage')->set('payment_method', 'qris')->assertViewHas('rows', fn ($page) => $page->currentPage() === 1 && $page->total() === 0);
    }

    public function test_date_and_status_filters_reset_list_page(): void
    {
        $this->localFixtures();
        Livewire::test(Index::class)->call('nextPage')->set('status', 'dikerjakan')
            ->assertViewHas('orders', fn ($page) => $page->currentPage() === 1 && $page->total() === 0);
        Livewire::test(\App\Livewire\Expenses\Index::class)->call('nextPage')->set('to', '2026-11-18')
            ->assertViewHas('expenses', fn ($page) => $page->currentPage() === 1 && $page->total() === 0);
        Livewire::test(\App\Livewire\Reports\Index::class)->call('nextPage')->set('to', '2026-11-18')
            ->assertViewHas('rows', fn ($page) => $page->currentPage() === 1 && $page->total() === 0);
    }

    public function test_platform_search_by_store_owner_username_preserves_summary_and_query(): void
    {
        config(['repair.platform_owner.username' => 'qa.platform', 'repair.platform_owner.password_hash' => password_hash('synthetic-qa', PASSWORD_BCRYPT)]);
        for ($i = 1; $i <= 25; $i++) {
            $tenant = Tenant::create(['name' => sprintf('Client %03d', $i), 'is_active' => $i % 2 === 0]);
            app(TenantContext::class)->set($tenant);
            User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'name' => sprintf('Owner %03d', $i), 'username' => 'qa.owner.'.$i]);
        }
        $this->post('/platform/login', ['username' => 'qa.platform', 'password' => 'synthetic-qa'])->assertRedirect('/platform');
        $this->get('/platform?search=Client')->assertOk()->assertViewHas('tenants', fn ($page) => $page->total() === 25 && count($page) === 20 && str_contains($page->nextPageUrl(), 'search=Client'))
            ->assertViewHas('totalTenants', 26)->assertViewHas('activeTenants', 13);
        $this->get('/platform?search=Client&page=2')->assertOk()->assertViewHas('tenants', fn ($page) => $page->currentPage() === 2 && count($page) === 5)
            ->assertViewHas('owners', fn ($owners) => $owners->count() === 5);
        $this->get('/platform?search=Owner%20025')->assertOk()->assertViewHas('tenants', fn ($page) => $page->total() === 1);
        $this->get('/platform?search=qa.owner.25')->assertOk()->assertViewHas('tenants', fn ($page) => $page->total() === 1);
        $this->get('/platform?search=not-found')->assertOk()->assertViewHas('tenants', fn ($page) => $page->total() === 0);
    }
}
