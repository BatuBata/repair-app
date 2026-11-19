<?php

namespace Tests\Feature\Invoice;

use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Livewire\Dashboard\Index as Dashboard;
use App\Livewire\Tts\Detail;
use App\Livewire\Tts\Index;
use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class InspectionTest extends TestCase
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

    private function order(): ServiceOrder
    {
        return app(CreateServiceOrder::class)->create(['customer_name' => 'Pelanggan cek', 'customer_phone' => '081234567890', 'category_id' => Category::firstOrCreate(['name' => 'HP'])->id, 'complaint' => 'Tidak mengisi daya'], $this->admin)->refresh();
    }

    private function context(): void
    {
        app(TenantContext::class)->set(Tenant::findOrFail($this->admin->tenant_id));
    }

    public function test_inspection_save_send_and_start_work_with_correct_dates(): void
    {
        $order = $this->order();
        Livewire::test(Detail::class, ['id' => $order->id])->assertSet('assignment_stage', 'pengecekan')->set('technician_id', (string) $this->technician->id)->call('assign')->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('pengecekan', $order->status->value);
        $this->assertNotNull($order->inspection_started_at);
        $this->assertNull($order->work_started_at);
        $this->get(route('invoice.edit', $order->id))->assertForbidden();
        $this->context();
        Livewire::test(Detail::class, ['id' => $order->id])->set('inspection_result', " Baterai drop & konektor longgar\nGanti baterai\nUji pengisian ")->call('sendInspection')->assertHasNoErrors()->assertRedirect(route('tts.whatsapp', ['id' => $order->id, 'inspection' => 1]));
        $this->assertSame("Baterai drop & konektor longgar\nGanti baterai\nUji pengisian", $order->fresh()->inspection_result);
        $this->assertNull($order->fresh()->action_plan);
        $wa = $this->get(route('tts.whatsapp', ['id' => $order->id, 'inspection' => 1]))->assertRedirect();
        $message = rawurldecode($wa->headers->get('Location'));
        $this->assertStringContainsString('Baterai drop & konektor longgar', $message);
        $this->assertStringContainsString("Ganti baterai\nUji pengisian", $message);
        $this->assertStringContainsString('Mohon konfirmasi', $message);
        $this->assertStringNotContainsString('sudah selesai', $message);
        $this->context();
        Livewire::test(Index::class)->set('status', 'pengecekan')->assertViewHas('orders', fn ($v) => $v->total() === 1);
        Livewire::test(Dashboard::class)->assertViewHas('lane', fn ($v) => $v['pengecekan']['count'] === 1);
        $this->get('/t/'.$order->tracking_token)->assertOk()->assertSee('Barang sedang dalam pengecekan')->assertDontSee('Baterai drop')->assertDontSee('Pelanggan cek');
        $this->context();
        $this->travel(1)->hours();
        $started = $order->inspection_started_at;
        Livewire::test(Detail::class, ['id' => $order->id])->call('startWork')->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('dikerjakan', $order->status->value);
        $this->assertTrue($order->work_started_at->greaterThan($started));
        $this->assertEquals($started, $order->inspection_started_at);
        $this->get(route('invoice.edit', $order->id))->assertOk();
    }

    public function test_direct_work_needs_no_check_fields_and_reassignment_preserves_stage(): void
    {
        $order = $this->order();
        Livewire::test(Detail::class, ['id' => $order->id])->set('assignment_stage', 'dikerjakan')->set('technician_id', (string) $this->technician->id)->call('assign')->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('dikerjakan', $order->status->value);
        $this->assertNull($order->inspection_result);
        $this->assertNull($order->inspection_started_at);
        $this->assertNotNull($order->work_started_at);
        $started = $order->work_started_at;
        $this->travel(1)->hours();
        $other = User::factory()->create(['role' => 'teknisi']);
        app(ManageServiceOrder::class)->assign($order->id, $other->id, 'pengecekan');
        $this->assertSame('dikerjakan', $order->fresh()->status->value);
        $this->assertEquals($started, $order->fresh()->work_started_at);
        $this->get(route('invoice.edit', $order->id))->assertOk();
        $this->get('/t/'.$order->tracking_token)->assertOk()->assertDontSee('Menunggu hasil pengecekan');
    }

    public function test_inspection_validation_cancellation_and_stale_actions(): void
    {
        $order = $this->order();
        $action = app(ManageServiceOrder::class);
        $action->assign($order->id, $this->technician->id, 'pengecekan');
        $form = Livewire::test(Detail::class, ['id' => $order->id]);
        $form->call('sendInspection')->assertHasErrors(['inspection_result']);
        $form->set('inspection_result', str_repeat('x', 8101))->call('saveInspection')->assertHasErrors('inspection_result');
        $this->get(route('tts.whatsapp', ['id' => $order->id, 'inspection' => 1]))->assertStatus(422);
        $this->context();
        $form->set('inspection_result', '<script>alert(1)</script>')->call('saveInspection')->assertHasNoErrors();
        $this->get(route('tts.show', $order->id))->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->context();
        $action->cancel($order->id, 'Pelanggan menolak');
        $form->call('startWork')->assertHasErrors('status');
        $form->call('saveInspection')->assertHasErrors('status');
        $this->assertSame('batal', $order->fresh()->status->value);
    }

    public function test_start_work_cannot_skip_assignment_or_be_repeated(): void
    {
        $order = $this->order();
        $action = app(ManageServiceOrder::class);
        try {
            $action->startWork($order->id);
            $this->fail('Unassigned order started');
        } catch (ValidationException) {
            $this->assertSame('diterima', $order->fresh()->status->value);
        }
        $action->assign($order->id, $this->technician->id, 'pengecekan');
        $action->startWork($order->id);
        $time = $order->fresh()->work_started_at;
        try {
            $action->startWork($order->id);
            $this->fail('Repeated start accepted');
        } catch (ValidationException) {
            $this->assertEquals($time, $order->fresh()->work_started_at);
        }
    }

    public function test_start_work_preserves_unsaved_notes_and_rejects_oversized_notes(): void
    {
        $order = $this->order();
        app(ManageServiceOrder::class)->assign($order->id, $this->technician->id, 'pengecekan');
        $form = Livewire::test(Detail::class, ['id' => $order->id]);
        $form->set('inspection_result', str_repeat('x', 8101))->call('startWork')->assertHasErrors('inspection_result');
        $this->assertSame('pengecekan', $order->fresh()->status->value);
        Livewire::test(Detail::class, ['id' => $order->id])->set('inspection_result', 'Baterai drop. Ganti baterai.')->call('startWork')->assertHasNoErrors();
        $this->assertSame('dikerjakan', $order->fresh()->status->value);
        $this->assertSame('Baterai drop. Ganti baterai.', $order->fresh()->inspection_result);
    }

    public function test_admin_and_tenant_guards_cover_all_new_actions_and_whatsapp(): void
    {
        $order = $this->order();
        app(ManageServiceOrder::class)->assign($order->id, $this->technician->id, 'pengecekan');
        $otherTenant = Tenant::create(['name' => 'Toko lain']);
        $foreign = User::factory()->create(['role' => 'admin', 'tenant_id' => $otherTenant->id]);
        $this->actingAs($foreign);
        Livewire::test(Detail::class, ['id' => $order->id])->assertNotFound();
        $this->get(route('tts.whatsapp', ['id' => $order->id, 'inspection' => 1]))->assertNotFound();
        $this->actingAs($this->technician);
        $this->context();
        $component = Livewire::test(Detail::class, ['id' => $order->id]);
        $component->call('startWork')->assertForbidden();
        Livewire::test(Detail::class, ['id' => $order->id])->set('inspection_result', 'Hasil dan rencana')->call('saveInspection')->assertForbidden();
        $this->get(route('tts.whatsapp', ['id' => $order->id, 'inspection' => 1]))->assertForbidden();
        $this->assertSame('pengecekan', $order->fresh()->status->value);
    }

    public function test_legacy_two_fields_are_merged_without_loss_in_form_detail_and_wa(): void
    {
        $order = $this->order();
        app(ManageServiceOrder::class)->assign($order->id, $this->technician->id, 'pengecekan');
        $order->update(['inspection_result' => 'Baterai drop', 'action_plan' => 'Ganti baterai']);
        $notes = "Baterai drop\n\nRencana tindakan:\nGanti baterai";
        $component = Livewire::test(Detail::class, ['id' => $order->id])->assertSet('inspection_result', $notes);
        $this->assertSame(1, substr_count($component->html(), '<textarea'));
        $wa = $this->get(route('tts.whatsapp', ['id' => $order->id, 'inspection' => 1]));
        $this->assertStringContainsString($notes, rawurldecode($wa->headers->get('Location')));
        $this->context();
        $component->call('saveInspection')->assertHasNoErrors();
        $this->assertSame($notes, $order->fresh()->inspection_result);
        $this->assertNull($order->fresh()->action_plan);
        Livewire::test(Detail::class, ['id' => $order->id])->assertSet('inspection_result', $notes);
        app(ManageServiceOrder::class)->startWork($order->id);
        $this->get(route('tts.show', $order->id))->assertSee('Baterai drop')->assertSee('Ganti baterai');
    }

    public function test_migration_preserves_legacy_status_and_refuses_unsafe_rollback(): void
    {
        $order = $this->order();
        $order->update(['status' => 'dikerjakan', 'assigned_at' => '2026-11-18 10:00:00']);
        $migration = require database_path('migrations/2026_11_19_000000_add_optional_inspection.php');
        $migration->down();
        $before = (array) DB::table('service_orders')->where('id', $order->id)->first();
        $migration->up();
        $after = (array) DB::table('service_orders')->where('id', $order->id)->first();
        foreach (['inspection_result', 'action_plan', 'inspection_started_at', 'work_started_at'] as $field) {
            $this->assertNull($after[$field]);
            unset($after[$field]);
        }
        $this->assertSame($before, $after);
        $checking = $this->order();
        app(ManageServiceOrder::class)->assign($checking->id, $this->technician->id, 'pengecekan');
        try {
            $migration->down();
            $this->fail('Rollback removed active checking status');
        } catch (RuntimeException) {
            $this->assertTrue(Schema::hasColumn('service_orders', 'inspection_result'));
            $this->assertSame('pengecekan', $checking->fresh()->status->value);
        }
    }
}
