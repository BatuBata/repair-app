<?php

namespace Tests\Feature\Invoice;

use App\Actions\ServiceOrders\InvoiceLines;
use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Enums\ServiceOrderStatus;
use App\Livewire\Invoice\Form;
use App\Livewire\Tts\Detail;
use App\Livewire\Tts\Index;
use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\CreateServiceOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $technician;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->technician = User::factory()->create(['role' => 'teknisi']);
        $this->actingAs($this->admin);
    }

    private function order(string $status = 'diterima'): ServiceOrder
    {
        $order = app(CreateServiceOrder::class)->create([
            'customer_name' => 'Bu Ratna', 'customer_phone' => '081234567890', 'complaint' => 'LCD bergaris',
            'category_id' => Category::firstOrCreate(['name' => 'Laptop'])->id,
        ], $this->technician);
        if ($status !== 'diterima') {
            app(ManageServiceOrder::class)->assign($order->id, $this->technician->id);
        }
        if ($status !== 'diterima' && $status !== 'dikerjakan') {
            $order->update(['status' => $status]);
        }

        return $order->refresh();
    }

    private function input(array $changes = []): array
    {
        return array_replace([
            'rows' => [
                ['type' => 'part', 'description' => 'LCD 14 inci HD', 'qty' => 1, 'price' => 1000000, 'cost' => 700000],
                ['type' => 'jasa', 'description' => 'Jasa ganti LCD', 'qty' => 1, 'price' => 100000, 'cost' => 5000],
            ], 'warranty_days' => 30, 'unrepairable' => false,
        ], $changes);
    }

    private function issue(ServiceOrder $order, array $changes = []): void
    {
        app(ManageServiceOrder::class)->invoice($order->id, $this->input($changes), false, InvoiceLines::revision($order->fresh()));
    }

    public function test_assign_and_change_only_active_technician_and_duplicate_assignment_keeps_time(): void
    {
        $order = $this->order();
        $action = app(ManageServiceOrder::class);
        $this->travelTo(now()->setTime(10, 0));
        $action->assign($order->id, $this->technician->id);
        $this->assertSame('dikerjakan', $order->fresh()->status->value);
        $assigned = $order->fresh()->assigned_at->toDateTimeString();
        $this->travel(1)->hours();
        $action->assign($order->id, $this->technician->id);
        $this->assertSame($assigned, $order->fresh()->assigned_at->toDateTimeString());
        $peer = User::factory()->create(['role' => 'teknisi']);
        $action->assign($order->id, $peer->id);
        $this->assertSame($peer->id, $order->fresh()->technician_id);
        $this->assertNotSame($assigned, $order->fresh()->assigned_at->toDateTimeString());
        $peer->update(['is_active' => false]);
        $this->get(route('tts.show', $order->id))->assertSee($peer->name);
        $html = Livewire::test(Detail::class, ['id' => $order->id])->html();
        $this->assertStringNotContainsString('<option value="'.$peer->id.'">', $html);
        foreach ([$peer->id, $this->admin->id, 99999, -1, [], 'bad'] as $id) {
            try {
                $action->assign($order->id, $id);
                $this->fail('Invalid technician was accepted.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('technician_id', $e->errors());
            }
        }
    }

    public static function states(): array
    {
        return array_map(fn ($status) => [$status->value], ServiceOrderStatus::cases());
    }

    #[DataProvider('states')]
    public function test_pickup_and_cancel_allow_only_legal_statuses_and_cannot_repeat(string $status): void
    {
        $action = app(ManageServiceOrder::class);
        $order = $this->order($status);
        try {
            $action->collect($order->id, '2026-11-13');
            $this->assertSame('selesai', $status);
            $this->assertSame('diambil', $order->fresh()->status->value);
            $this->assertSame('2026-11-13', $order->fresh()->picked_up_on->toDateString());
        } catch (ValidationException $e) {
            $this->assertNotSame('selesai', $status);
            $this->assertArrayHasKey('status', $e->errors());
        }
        $other = $this->order($status);
        try {
            $action->cancel($other->id, 'Customer tidak jadi');
            $this->assertContains($status, ['diterima', 'dikerjakan']);
            $this->assertSame('batal', $other->fresh()->status->value);
            $this->assertSame('Customer tidak jadi', $other->fresh()->cancel_reason);
            $this->assertNotNull($other->fresh()->cancelled_at);
            try {
                $action->cancel($other->id, 'Double');
                $this->fail('Repeated cancel accepted.');
            } catch (ValidationException) {
                $this->assertSame('Customer tidak jadi', $other->fresh()->cancel_reason);
            }
        } catch (ValidationException $e) {
            $this->assertNotContains($status, ['diterima', 'dikerjakan']);
        }
    }

    #[DataProvider('states')]
    public function test_issue_only_in_progress_and_technician_is_required(string $status): void
    {
        $order = $this->order($status);
        if ($status === 'dikerjakan') {
            $order->update(['technician_id' => null]);
        }
        $this->expectException(ValidationException::class);
        $this->issue($order);
    }

    public function test_issue_forces_jasa_zero_and_dates_warranty_once_in_wita(): void
    {
        $this->travelTo(now('UTC')->setDate(2026, 11, 10)->setTime(17, 0));
        $order = $this->order('dikerjakan');
        $this->issue($order);
        $order->refresh();
        $this->assertSame('selesai', $order->status->value);
        $this->assertSame('2026-11-11', $order->invoiced_on->toDateString());
        $this->assertSame('2026-12-11', $order->warranty_until->toDateString());
        $this->assertSame(0, $order->items()->where('type', 'jasa')->firstOrFail()->cost);
        $total = InvoiceLines::calculate($order->items()->get()->toArray());
        $this->assertSame(1100000, $total['price']);
        $this->assertSame(700000, $total['cost']);
        $this->assertSame(400000, $total['profit']);
        try {
            $this->issue($order);
            $this->fail('Repeated issue accepted.');
        } catch (ValidationException) {
            $this->assertSame(2, $order->items()->count());
        }
    }

    public function test_correction_preserves_invoice_date_status_and_pickup_and_rejects_stale_save(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 11));
        $order = $this->order('dikerjakan');
        $this->issue($order);
        $this->travel(4)->days();
        app(ManageServiceOrder::class)->collect($order->id, '2026-11-15');
        $order->refresh();
        $revision = InvoiceLines::revision($order);
        app(ManageServiceOrder::class)->invoice($order->id, $this->input(['warranty_days' => 60]), true, $revision);
        $order->refresh();
        $this->assertSame('diambil', $order->status->value);
        $this->assertSame('2026-11-15', $order->picked_up_on->toDateString());
        $this->assertSame('2026-11-11', $order->invoiced_on->toDateString());
        $this->assertSame('2027-01-10', $order->warranty_until->toDateString());
        $this->expectException(ValidationException::class);
        app(ManageServiceOrder::class)->invoice($order->id, $this->input(), true, $revision);
    }

    public function test_zero_total_unrepairable_default_warranty_and_dynamic_rows(): void
    {
        StoreSetting::create(['name' => '', 'address' => '', 'phone' => '', 'default_warranty_days' => 45]);
        $order = $this->order('dikerjakan');
        $c = Livewire::test(Form::class, ['id' => $order->id])->assertSet('warranty_days', '45')->call('addRow')->assertCount('rows', 2)->call('removeRow', 0)->assertCount('rows', 1);
        $c->set('rows.0.description', 'Biaya cek gratis')->set('unrepairable', true)->set('warranty_days', '0')->call('save')->assertHasNoErrors()->assertRedirect(route('tts.show', $order->id));
        $order->refresh();
        $this->assertTrue($order->unrepairable);
        $this->assertSame($order->invoiced_on->toDateString(), $order->warranty_until->toDateString());
        $this->assertSame(0, InvoiceLines::calculate($order->items()->get()->toArray())['price']);
    }

    public function test_invalid_lines_dates_and_money_bounds_leave_old_invoice_unchanged(): void
    {
        $order = $this->order('dikerjakan');
        $this->issue($order);
        $revision = InvoiceLines::revision($order->fresh());
        $bad = [
            ['rows' => []], ['rows' => [['type' => 'part', 'description' => '', 'qty' => 1, 'price' => 1, 'cost' => 0]]],
            ['rows' => [['type' => 'part', 'description' => 'X', 'qty' => 0, 'price' => 1, 'cost' => 0]]],
            ['rows' => [['type' => 'part', 'description' => 'X', 'qty' => 1, 'price' => -1, 'cost' => 0]]],
            ['rows' => [['type' => 'part', 'description' => 'X', 'qty' => 4294967295, 'price' => 4294967295, 'cost' => 0]]],
            ['rows' => array_fill(0, 2, ['type' => 'part', 'description' => 'X', 'qty' => 2147483647, 'price' => 4294967295, 'cost' => 0])],
            ['warranty_days' => 4294967295], ['warranty_days' => -1],
        ];
        foreach ($bad as $changes) {
            try {
                app(ManageServiceOrder::class)->invoice($order->id, $this->input($changes), true, $revision);
                $this->fail('Invalid invoice accepted.');
            } catch (ValidationException) {
                $this->assertSame($revision, InvoiceLines::revision($order->fresh()));
            }
        }
        foreach (['2026-02-30', '13-11-2026', '', '10000-01-01'] as $date) {
            try {
                app(ManageServiceOrder::class)->collect($order->id, $date);
                $this->fail('Invalid pickup date accepted.');
            } catch (ValidationException) {
                $this->assertSame('selesai', $order->fresh()->status->value);
            }
        }
    }

    public function test_database_failure_rolls_back_replacement_and_status(): void
    {
        $order = $this->order('dikerjakan');
        $this->issue($order);
        $revision = InvoiceLines::revision($order->fresh());
        $attempt = 0;
        ServiceOrderItem::creating(function () use (&$attempt) {
            if (++$attempt === 2) {
                throw new RuntimeException('Synthetic item write failure');
            }
        });
        try {
            app(ManageServiceOrder::class)->invoice($order->id, $this->input(), true, $revision);
            $this->fail('Write failure was swallowed.');
        } catch (RuntimeException $e) {
            $this->assertSame('Synthetic item write failure', $e->getMessage());
            $this->assertSame($revision, InvoiceLines::revision($order->fresh()));
        }
    }

    public function test_direct_and_livewire_actions_recheck_current_admin_and_technician_gets_no_money(): void
    {
        $order = $this->order('dikerjakan');
        $this->issue($order);
        $c = Livewire::test(Form::class, ['id' => $order->id]);
        $detail = Livewire::test(Detail::class, ['id' => $order->id]);
        User::whereKey($this->admin->id)->update(['role' => 'teknisi']);
        $c->call('save')->assertForbidden();
        $c = $detail->call('$refresh')->assertDontSee('700.000')->assertDontSee('1.100.000')->assertDontSee('Koreksi nota');
        $this->assertStringNotContainsString('700000', json_encode($c->snapshot));
        foreach ([route('tts.show', $order->id), route('tts.index'), route('tts.success', $order->id)] as $path) {
            $this->get($path)->assertOk()->assertDontSee('700.000')->assertDontSee('1.100.000')->assertDontSee('LCD 14 inci HD')->assertDontSee('700000');
        }
        $this->get(route('invoice.edit', $order->id))->assertForbidden();
        Livewire::test(Form::class, ['id' => $order->id])->assertForbidden();
        Livewire::test(Index::class)->assertDontSee('700000');
        $revision = InvoiceLines::revision($order->fresh());
        try {
            app(ManageServiceOrder::class)->cancel($order->id, 'Forged');
            $this->fail('Demoted actor could mutate.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame($revision, InvoiceLines::revision($order->fresh()));
    }

    public function test_published_correction_changes_amounts_without_changing_original_date(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 11));
        $order = $this->order('dikerjakan');
        $this->issue($order);
        $this->travel(2)->days();
        Livewire::test(Form::class, ['id' => $order->id])->assertSet('rows.0.type', 'part')
            ->set('rows.0.qty', 2)->set('rows.0.price', 600000)->set('rows.0.cost', 400000)->set('unrepairable', true)
            ->call('save')->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('selesai', $order->status->value);
        $this->assertSame('2026-11-11', $order->invoiced_on->toDateString());
        $this->assertSame(1300000, InvoiceLines::calculate($order->items()->get()->toArray())['price']);
        $this->assertSame(800000, InvoiceLines::calculate($order->items()->get()->toArray())['cost']);
        $this->assertTrue($order->unrepairable);
    }

    public function test_invalid_ids_are_validated_before_order_queries_and_inactive_actor_cannot_write(): void
    {
        $queries = 0;
        DB::listen(function ($query) use (&$queries) {
            if (str_contains($query->sql, 'service_orders')) {
                $queries++;
            }
        });
        foreach ([[], 'bad', -1] as $id) {
            try {
                app(ManageServiceOrder::class)->assign($id, $this->technician->id);
                $this->fail('Invalid order ID accepted.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('id', $e->errors());
            }
        }
        $this->assertSame(0, $queries);
        $order = $this->order();
        $component = Livewire::test(Detail::class, ['id' => $order->id]);
        User::whereKey($this->admin->id)->update(['is_active' => false]);
        $component->call('assign')->assertForbidden();
        $this->assertSame('diterima', $order->fresh()->status->value);
    }

    public function test_closed_status_cannot_reassign_and_forged_correction_mode_is_locked(): void
    {
        foreach (['selesai', 'diambil', 'batal'] as $status) {
            $order = $this->order($status);
            try {
                app(ManageServiceOrder::class)->assign($order->id, $this->technician->id);
                $this->fail('Closed order reassigned.');
            } catch (ValidationException) {
                $this->assertSame($status, $order->fresh()->status->value);
            }
        }
        $order = $this->order('dikerjakan');
        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::test(Form::class, ['id' => $order->id])->set('correction', true);
    }

    public function test_large_totals_stay_integer_and_format_boundary_digits_exactly(): void
    {
        $rows = [
            ['type' => 'part', 'qty' => 2147483647, 'price' => 4294967295, 'cost' => 0],
            ['type' => 'part', 'qty' => 1, 'price' => 4294967295, 'cost' => 0],
            ['type' => 'part', 'qty' => 1, 'price' => 2147483647, 'cost' => 0],
        ];
        $total = InvoiceLines::calculate($rows)['price'];
        $this->assertIsInt($total);
        $this->assertSame(PHP_INT_MAX, $total);
        $this->assertSame('9.223.372.036.854.775.807', number_format($total, 0, ',', '.'));
        $this->assertSame('-300.000', number_format(-300000, 0, ',', '.'));
        $this->assertSame('0', number_format(0, 0, ',', '.'));
    }

    public function test_stale_status_and_locked_form_mode_cannot_issue_as_correction(): void
    {
        $order = $this->order('dikerjakan');
        $form = Livewire::test(Form::class, ['id' => $order->id])->set('rows', $this->input()['rows']);
        app(ManageServiceOrder::class)->cancel($order->id, 'Sudah batal');
        $form->call('save')->assertForbidden();
        $this->assertDatabaseCount('service_order_items', 0);
    }
}
