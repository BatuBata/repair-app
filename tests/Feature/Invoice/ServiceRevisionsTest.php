<?php

namespace Tests\Feature\Invoice;

use App\Actions\ServiceOrders\InvoiceLines;
use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Livewire\Dashboard\Index as Dashboard;
use App\Livewire\Invoice\Form;
use App\Livewire\Reports\Index as Reports;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Documents\Document;
use App\Services\Reports\Report;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceRevisionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($this->admin);
        $this->travelTo(now('Asia/Makassar')->setDate(2026, 11, 18)->setTime(10, 0));
    }

    private function order(int $discount = 0): ServiceOrder
    {
        $order = ServiceOrder::create(['number' => 'TTS-TEST-'.(ServiceOrder::count() + 1), 'customer_name' => 'Pelanggan sintetis', 'customer_phone' => '081234567890', 'category_id' => Category::firstOrCreate(['name' => 'HP'])->id, 'complaint' => 'Layar rusak', 'status' => 'dikerjakan', 'created_by' => $this->admin->id, 'technician_id' => $this->admin->id]);
        app(ManageServiceOrder::class)->invoice($order->id, ['rows' => [['type' => 'part', 'description' => 'Ganti layar', 'qty' => 2, 'price' => 50000, 'cost' => 10000]], 'discount' => $discount, 'warranty_days' => 0, 'unrepairable' => false], false, InvoiceLines::revision($order->refresh()));

        return $order->refresh();
    }

    public function test_discount_validation_is_atomic_and_free_pickup_needs_no_payment(): void
    {
        $order = $this->order(100000);
        $action = app(ManageServiceOrder::class);
        $revision = InvoiceLines::revision($order);
        foreach ([-1, 100001, '1.5', [], '999999999999999999999'] as $discount) {
            try {
                $action->invoice($order->id, ['rows' => $order->items()->get(['type', 'description', 'qty', 'price', 'cost'])->toArray(), 'discount' => $discount, 'warranty_days' => 0, 'unrepairable' => false], true, $revision);
                $this->fail('Invalid discount accepted');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('discount', $e->errors());
                $this->assertSame($revision, InvoiceLines::revision($order->fresh()));
            }
        }
        try {
            $action->recordPayment($order->id, ['payment_method' => 'cash']);
            $this->fail('Free invoice accepted a payment');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('payment_method', $e->errors());
        }
        $this->get(route('tts.show', $order->id))->assertOk()->assertSee('Gratis')->assertSee('Tandai Diambil')->assertDontSee('Catat pembayaran')->assertDontSee('Bayar &amp; Diambil', false);
        app(TenantContext::class)->set(Tenant::findOrFail($this->admin->tenant_id));
        $action->collect($order->id, '2026-11-18');
        $this->assertSame('diambil', $order->fresh()->status->value);
        $this->assertNull($order->fresh()->paid_at);
    }

    public function test_form_correction_and_all_financial_outputs_use_final_total(): void
    {
        $order = $this->order(25000);
        Livewire::test(Form::class, ['id' => $order->id])->assertSet('discount', '25000')->assertViewHas('totals', fn ($v) => $v['price'] === 75000 && $v['profit'] === 55000)
            ->set('discount', '100000')->call('save')->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('2026-11-18', $order->invoiced_on->toDateString());
        $report = app(Report::class)->build('2026-11-18', '2026-11-18');
        $this->assertSame('0', $report['revenue']);
        $this->assertSame('-20000', $report['gross']);
        $this->assertSame('0', $report['days'][0]['amount']);
        Livewire::test(Dashboard::class)->assertViewHas('revenue', ['total' => 'Rp0', 'service' => 'Rp0', 'sales' => 'Rp0']);
        $this->get(route('tts.print', $order->id))->assertOk()->assertSee('Diskon')->assertSee('Rp0 (Gratis)')->assertDontSee('20.000');
        $this->get('/t/'.$order->tracking_token)->assertOk()->assertSee('Diskon')->assertSee('Rp0 (Gratis)')->assertDontSee('20.000')->assertDontSee('Pelanggan sintetis');
        $response = $this->get(route('tts.whatsapp', $order->id))->assertRedirect();
        $this->assertStringContainsString('Total Rp0 (Gratis)', rawurldecode($response->headers->get('Location')));
        app(TenantContext::class)->set(Tenant::findOrFail($this->admin->tenant_id));
        $data = app(Document::class)->order($order->id, false, true);
        $this->assertSame(100000, $data['discount']);
        foreach (['a5', 'thermal'] as $format) {
            $pdf = app(Document::class)->respond($data, $format);
            $this->assertSame('application/pdf', $pdf->headers->get('Content-Type'));
            $this->assertStringStartsWith('%PDF', $pdf->getContent());
        }
    }

    public function test_correction_to_free_clears_payment_and_recharging_requires_payment_again(): void
    {
        $order = $this->order();
        $action = app(ManageServiceOrder::class);
        $action->recordPayment($order->id, ['payment_method' => 'transfer', 'payment_bank' => 'BCA']);
        Livewire::test(Form::class, ['id' => $order->id])->set('discount', '100000')->call('save')->assertHasNoErrors();
        foreach (['paid_at', 'payment_method', 'payment_bank'] as $field) {
            $this->assertNull($order->fresh()->$field);
        }
        Livewire::test(Form::class, ['id' => $order->id])->set('discount', '0')->call('save')->assertHasNoErrors();
        $this->expectException(ValidationException::class);
        $action->collect($order->id, '2026-11-18');
    }

    public function test_payment_summary_combines_sales_and_service_by_transaction_date_and_bank(): void
    {
        $action = app(ManageServiceOrder::class);
        foreach ([['cash', null, 0], ['qris', null, 10000], ['transfer', 'BCA', 20000], ['transfer', 'Bank Mandiri', 0], [null, null, 0]] as [$method, $bank, $discount]) {
            $order = $this->order($discount);
            if ($method) {
                $action->recordPayment($order->id, ['payment_method' => $method, 'payment_bank' => $bank]);
                $order->update(['paid_at' => '2026-12-01 10:00:00']);
            }
        }
        $product = Product::create(['name' => 'Kabel', 'cost_price' => 0, 'sell_price' => 50000, 'stock' => 0]);
        $sale = Sale::create(['number' => 'JL-TEST', 'sold_on' => '2026-11-18', 'created_by' => $this->admin->id, 'payment_method' => 'transfer', 'payment_bank' => 'BCA']);
        $sale->items()->create(['product_id' => $product->id, 'product_name' => 'Kabel', 'qty' => 1, 'price' => 50000, 'cost' => 0]);
        $outside = $this->order();
        $outside->update(['invoiced_on' => '2026-11-17', 'paid_at' => now(), 'payment_method' => 'cash']);
        Expense::create(['spent_on' => '2026-11-18', 'amount' => 5000, 'category' => 'bensin', 'created_by' => $this->admin->id]);
        $report = app(Report::class)->build('2026-11-18', '2026-11-18');
        $this->assertSame(['cash' => '100000', 'qris' => '90000', 'transfer' => '230000', 'unrecorded' => '100000', 'total' => '420000'], $report['payments']);
        $this->assertSame(['BCA' => '130000', 'Bank Mandiri' => '100000'], $report['paymentBanks']);
        $this->assertSame('520000', $report['revenue']);
        Livewire::test(Reports::class)->set('from', '2026-11-18')->set('to', '2026-11-18')->assertSee('Ringkasan pembayaran')->assertSee('Rincian transfer')->set('payment_method', 'qris')->assertViewHas('report', fn ($v) => $v['payments']['total'] === '420000')->assertViewHas('rows', fn ($v) => count($v) === 1);
    }

    public function test_unrepairable_whatsapp_does_not_claim_success_or_cancel_internal_status(): void
    {
        $order = $this->order(25000);
        $normal = rawurldecode($this->get(route('tts.whatsapp', $order->id))->headers->get('Location'));
        $this->assertStringContainsString('sudah selesai', $normal);
        app(TenantContext::class)->set(Tenant::findOrFail($this->admin->tenant_id));
        $order->update(['unrepairable' => true]);
        $message = rawurldecode($this->get(route('tts.whatsapp', $order->id))->headers->get('Location'));
        $this->assertStringContainsString('tidak dapat dilanjutkan karena barang tidak dapat diperbaiki', $message);
        $this->assertStringContainsString('Total Rp75.000', $message);
        $this->assertStringNotContainsString('sudah selesai', $message);
        $this->assertSame('selesai', $order->fresh()->status->value);
    }
}
