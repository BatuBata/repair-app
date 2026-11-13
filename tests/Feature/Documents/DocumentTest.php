<?php

namespace Tests\Feature\Documents;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StoreSetting;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Services\Documents\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_obey_roles_and_public_invoice_gate(): void
    {
        $tech = User::factory()->create(['role' => 'teknisi']);
        $admin = User::factory()->create(['role' => 'admin']);
        $order = app(CreateServiceOrder::class)->create(['customer_name' => 'Synthetic Customer', 'customer_phone' => '081234567890', 'customer_address' => 'Secret Address', 'category_id' => Category::firstOrCreate(['name' => 'Laptop'])->id, 'item_description' => 'Model X', 'complaint' => 'Layar rusak'], $tech);
        $this->get(route('tracking.pdf', $order->tracking_token))->assertNotFound();
        $order->update(['status' => 'selesai', 'invoiced_on' => '2026-11-10', 'warranty_days' => 0, 'unrepairable' => true]);
        $order->items()->create(['type' => 'part', 'description' => 'LCD', 'qty' => 2, 'price' => 100000, 'cost' => 734567]);
        $this->actingAs($tech)->get(route('tts.print', $order->id))->assertOk()->assertSee('Tanda terima servis')->assertDontSee('200.000')->assertDontSee('734.567');
        $this->get(route('tts.receipt.pdf', $order->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $techDocument = app(Document::class)->order($order->id, false, false);
        $this->assertSame('Tanda terima servis', $techDocument['kind']);
        $this->assertCount(0, $techDocument['items']);
        $this->get(route('tts.pdf', $order->id))->assertForbidden();
        $this->get(route('sales.pdf', 1))->assertForbidden();
        $this->actingAs($admin)->get(route('tts.print', $order->id))->assertOk()->assertSee('200.000')->assertSee('Tidak dapat diperbaiki')->assertDontSee('734.567');
        $this->get(route('tts.receipt.pdf', $order->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('tts.pdf', $order->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('tracking.pdf', strtoupper($order->tracking_token)))->assertNotFound();
        $this->get(route('tracking.pdf', $order->tracking_token))->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Robots-Tag', 'noindex');
        $admin->update(['is_active' => false]);
        $this->get(route('tts.print', $order->id))->assertForbidden();
    }

    public function test_sale_receipt_keeps_large_integer_amounts_and_admin_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $tech = User::factory()->create(['role' => 'teknisi']);
        $product = Product::create(['name' => 'Barang sintetis', 'cost_price' => 1, 'sell_price' => 1, 'stock' => 1]);
        $sale = Sale::create(['number' => 'JL-2611-999', 'sold_on' => '2026-11-10', 'created_by' => $admin->id, 'warranty_days' => 0]);
        $sale->items()->create(['product_id' => $product->id, 'product_name' => 'Barang sintetis', 'qty' => 2147483648, 'price' => 4294967295, 'cost' => 1]);
        $this->actingAs($tech)->get(route('sales.print', $sale->id))->assertForbidden();
        $this->actingAs($admin)->get(route('sales.print', $sale->id))->assertOk()->assertSee('2147483648')->assertSee('Rp4.294.967.295')->assertSee('Rp9.223.372.034.707.292.160');
        $this->get(route('sales.receipt.pdf', $sale->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('sales.pdf', $sale->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_long_thermal_text_preserves_words_newlines_and_unbroken_text(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $address = "Awal α\n\n".str_repeat('Kata & teks ', 150)."\n".str_repeat('X', 1300).' akhir';
        $terms = "Awal\n".str_repeat("Ketentuan <uji>\n", 120).'Akhir';
        StoreSetting::updateOrCreate(['id' => 1], ['name' => 'Toko', 'address' => $address, 'phone' => '081234567890', 'warranty_terms' => $terms, 'default_warranty_days' => 30]);
        $sale = Sale::create(['number' => 'JL-2611-998', 'sold_on' => '2026-11-10', 'created_by' => $admin->id, 'warranty_days' => 0]);
        $response = $this->actingAs($admin)->get(route('sales.print', $sale->id))->assertOk();
        $dom = new \DOMDocument;
        $dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);
        $read = static function (\DOMNodeList $nodes): string {
            $text = '';
            foreach ($nodes as $node) {
                $text .= $node->textContent;
            }

            return $text;
        };
        $this->assertSame($address, $read($xpath->query('//header/p[@class="pre"]')));
        $this->assertSame($terms, $read($xpath->query('//section[last()]/p[@class="pre"]')));
    }
}
