<?php

namespace Tests\Feature\Tts;

use App\Livewire\Tts\Index;
use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Support\TenantContext;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CancelledPickupListTest extends TestCase
{
    use RefreshDatabase;

    private function order(User $creator, string $name, string $status, ?string $picked = null): ServiceOrder
    {
        app(TenantContext::class)->set(Tenant::findOrFail($creator->tenant_id));
        $order = app(CreateServiceOrder::class)->create([
            'customer_name' => $name, 'customer_phone' => '081234567890',
            'category_id' => Category::firstOrCreate(['name' => 'HP'])->id, 'complaint' => 'Tidak menyala',
        ], $creator);
        $order->update(['status' => $status, 'picked_up_on' => $picked]);

        return $order;
    }

    private function rowText(string $html, ServiceOrder $order): string
    {
        $document = new DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $row = (new DOMXPath($document))->query('//tr[.//a[@href="'.route('tts.show', $order->id).'"]]');
        $this->assertSame(1, $row->length);

        return $row->item(0)->textContent;
    }

    public function test_admin_and_technician_see_pickup_information_only_on_the_matching_cancelled_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'teknisi']);
        $unpicked = $this->order($admin, 'Batal belum diserahkan', 'batal');
        $picked = $this->order($admin, 'Batal sudah diserahkan', 'batal', '2026-11-19');
        $normal = $this->order($admin, 'Servis sukses biasa', 'diambil', '2026-11-19');
        foreach ([$admin, $technician] as $actor) {
            $this->actingAs($actor);
            $view = Livewire::test(Index::class)->assertViewHas('orders', fn ($orders) => $orders->total() === 3);
            $unpickedText = $this->rowText($view->html(), $unpicked);
            $pickedText = $this->rowText($view->html(), $picked);
            $normalText = $this->rowText($view->html(), $normal);
            $this->assertStringContainsString('Barang belum diambil', $unpickedText);
            $this->assertStringNotContainsString('Barang sudah diambil', $unpickedText);
            $this->assertStringContainsString('Barang sudah diambil', $pickedText);
            $this->assertStringNotContainsString('Barang belum diambil', $pickedText);
            $this->assertStringNotContainsString('Barang sudah diambil', $normalText);
            $this->assertStringNotContainsString('Barang belum diambil', $normalText);
            $view->set('status', 'batal')->assertViewHas('orders', fn ($orders) => $orders->total() === 2)
                ->set('q', 'Batal sudah')->assertViewHas('orders', fn ($orders) => $orders->total() === 1)
                ->assertSee('Barang sudah diambil')->assertDontSee('Barang belum diambil');
        }
    }

    public function test_cancelled_pickup_labels_never_expose_orders_from_another_tenant(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $local = $this->order($admin, 'Pelanggan lokal QA', 'batal');
        $other = Tenant::create(['name' => 'Toko asing QA']);
        $foreignAdmin = User::factory()->create(['tenant_id' => $other->id, 'role' => 'admin']);
        $this->order($foreignAdmin, 'Pelanggan rahasia QA', 'batal', '2026-11-19');
        app(TenantContext::class)->set(Tenant::findOrFail($admin->tenant_id));
        $this->actingAs($admin);
        Livewire::test(Index::class)->set('status', 'batal')->assertViewHas('orders', fn ($orders) => $orders->total() === 1 && $orders->first()->id === $local->id)
            ->assertSee('Barang belum diambil')->assertDontSee('Barang sudah diambil')->assertDontSee('Pelanggan rahasia QA')
            ->set('q', 'Pelanggan rahasia QA')->assertViewHas('orders', fn ($orders) => $orders->total() === 0)->assertDontSee('Barang sudah diambil');
    }
}
