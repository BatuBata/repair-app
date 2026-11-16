<?php

namespace Tests\Feature\Tenancy;

use App\Actions\ServiceOrders\InvoiceLines;
use App\Actions\ServiceOrders\ManageServiceOrder;
use App\Livewire\Invoice\Form as InvoiceForm;
use App\Livewire\Tts\Detail;
use App\Livewire\Tts\Form;
use App\Livewire\Tts\Index;
use App\Models\Category;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CreateServiceOrder;
use App\Services\DocumentNumber;
use App\Services\Documents\Document;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ServiceIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function context(User $user): void
    {
        app(TenantContext::class)->set(Tenant::findOrFail($user->tenant_id));
    }

    /** @return array{User, User, Category, ServiceOrder} */
    private function shop(string $name): array
    {
        $tenant = Tenant::create(['name' => $name]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin', 'name' => 'Owner '.$name]);
        $tech = User::factory()->create(['role' => 'teknisi', 'name' => 'Teknisi '.$name]);
        $category = Category::create(['name' => 'Kategori '.$name]);
        StoreSetting::create(['name' => 'Identitas '.$name, 'address' => 'Alamat toko '.$name, 'phone' => '6281234567890', 'warranty_terms' => 'Garansi '.$name]);
        $order = app(CreateServiceOrder::class)->create($this->input($category), $owner);

        return [$owner, $tech, $category, $order];
    }

    /** @return array<string, mixed> */
    private function input(Category $category): array
    {
        return ['customer_name' => 'Customer '.$category->name, 'customer_phone' => '081234567890', 'customer_address' => 'PRIVATE ADDRESS', 'category_id' => $category->id, 'item_description' => 'Barang '.$category->name, 'complaint' => 'Keluhan '.$category->name];
    }

    public function test_internal_lists_search_details_and_forms_only_see_active_tenant(): void
    {
        [$a, , $category, $own] = $this->shop('A');
        [, , , $foreign] = $this->shop('B');
        $this->actingAs($a);
        $this->get('/tts')->assertOk()->assertSee($own->customer_name)->assertDontSee($foreign->customer_name);
        $this->context($a);
        Livewire::test(Index::class)->set('q', $foreign->customer_name)->assertDontSee($foreign->customer_name)->assertDontSee($own->customer_name);
        foreach (['tts.show', 'tts.edit', 'tts.success', 'invoice.edit', 'tts.print', 'tts.receipt.pdf', 'tts.pdf', 'tts.whatsapp'] as $route) {
            $this->get(route($route, $foreign->id))->assertNotFound();
        }
        $this->context($a);
        Livewire::test(Form::class)->set('customer_name', 'Customer baru A')->set('customer_phone', '081234567890')->set('category_id', (string) $category->id)->set('complaint', 'Baru')->call('save')->assertHasNoErrors();
        $this->context($a);
        Livewire::test(Form::class, ['id' => $own->id])->set('complaint', 'Diubah A')->call('save')->assertHasNoErrors();
        $this->assertSame('Diubah A', $own->fresh()->complaint);
        $this->assertSame('Keluhan Kategori B', $foreign->fresh()->complaint);
    }

    public function test_foreign_actions_cannot_change_parent_or_items(): void
    {
        [$a, $tech] = $this->shop('A');
        [, , , $foreign] = $this->shop('B');
        $foreign->items()->create(['type' => 'part', 'description' => 'PRIVATE PART B', 'qty' => 1, 'price' => 100, 'cost' => 50]);
        $before = $foreign->fresh()->getAttributes();
        $items = $foreign->items()->get()->toArray();
        $this->actingAs($a);
        $this->context($a);
        $action = app(ManageServiceOrder::class);
        foreach ([fn () => $action->assign($foreign->id, $tech->id), fn () => $action->cancel($foreign->id, 'Forged'), fn () => $action->collect($foreign->id, '2026-11-17'), fn () => $action->invoice($foreign->id, [], false, '')] as $call) {
            try {
                $call();
                $this->fail('Foreign parent accepted.');
            } catch (ModelNotFoundException) {
                $this->assertSame($before, $foreign->fresh()->getAttributes());
                $this->assertSame($items, $foreign->items()->get()->toArray());
            }
        }
        try {
            $foreign->fresh()->forceFill(['tenant_id' => $a->tenant_id, 'complaint' => 'Forged'])->save();
            $this->fail('Foreign existing model transferred tenants.');
        } catch (HttpException $error) {
            $this->assertSame(404, $error->getStatusCode());
            $this->assertSame($before, $foreign->fresh()->getAttributes());
        }
        Livewire::test(Detail::class, ['id' => $foreign->id])->assertNotFound();
        Livewire::test(InvoiceForm::class, ['id' => $foreign->id])->assertNotFound();
    }

    public function test_foreign_category_technician_and_creator_are_rejected_without_mutation(): void
    {
        [$a, $techA, $categoryA, $own] = $this->shop('A');
        [$b, $techB, $categoryB] = $this->shop('B');
        $this->actingAs($a);
        $this->context($a);
        foreach (['category_id' => $categoryB->id, 'technician_id' => $techB->id, 'created_by' => $b->id] as $field => $id) {
            try {
                $own->fresh()->update([$field => $id]);
                $this->fail('Foreign relation accepted.');
            } catch (ValidationException $error) {
                $this->assertArrayHasKey($field, $error->errors());
            }
        }
        Livewire::test(Form::class, ['id' => $own->id])->set('category_id', (string) $categoryB->id)->call('save')->assertHasErrors('category_id');
        Livewire::test(Detail::class, ['id' => $own->id])->set('technician_id', (string) $techB->id)->call('assign')->assertHasErrors('technician_id');
        foreach ([[$categoryB, $a], [$categoryA, $b]] as [$category, $creator]) {
            try {
                app(CreateServiceOrder::class)->create($this->input($category), $creator);
                $this->fail('Foreign creation accepted.');
            } catch (ValidationException|HttpException) {
                $this->assertSame(1, ServiceOrder::count());
            }
        }
        $this->assertSame($categoryA->id, $own->fresh()->category_id);
        $this->assertSame($a->id, $own->fresh()->created_by);
        $this->assertNull($own->fresh()->technician_id);
        app(ManageServiceOrder::class)->assign($own->id, $techA->id);
        $own->refresh();
        app(ManageServiceOrder::class)->invoice($own->id, ['rows' => [['type' => 'jasa', 'description' => 'Jasa A', 'qty' => 1, 'price' => 100, 'cost' => 0]], 'warranty_days' => 0, 'unrepairable' => false], false, InvoiceLines::revision($own));
        $this->assertSame('Jasa A', $own->items()->firstOrFail()->description);
    }

    public function test_counters_tokens_and_missing_context_are_isolated(): void
    {
        [$a, , , $own] = $this->shop('A');
        [$b, , , $other] = $this->shop('B');
        $this->assertSame($own->number, $other->number);
        $this->assertNotSame($own->tracking_token, $other->tracking_token);
        foreach ([$a, $b] as $user) {
            $this->context($user);
            $numbers = DB::transaction(fn () => [app(DocumentNumber::class)->next('TTS'), app(DocumentNumber::class)->next('JL')]);
            $this->assertStringEndsWith('-002', $numbers[0]);
            $this->assertStringEndsWith('-001', $numbers[1]);
        }
        app(TenantContext::class)->forget();
        $this->assertSame(0, ServiceOrder::count());
        try {
            ServiceOrder::create(['number' => 'FORGED']);
            $this->fail('Order created without context.');
        } catch (\LogicException) {
            $this->assertSame(2, DB::table('service_orders')->count());
        }
        try {
            DB::transaction(fn () => app(DocumentNumber::class)->next('JL'));
            $this->fail('Number issued without context.');
        } catch (\LogicException) {
            $this->assertSame(4, DB::table('number_sequences')->count());
        }
    }

    public function test_empty_owner_settings_never_fall_back_to_another_shop_name(): void
    {
        $this->shop('Anugerah');
        [$owner, , , $order] = $this->shop('Sumber HP');
        StoreSetting::query()->update(['name' => '']);
        $this->actingAs($owner);
        $this->get(route('tts.print', $order->id))->assertOk()->assertSee('Sumber HP')->assertDontSee('Anugerah');
        $message = rawurldecode($this->get(route('tts.whatsapp', $order->id))->headers->get('Location'));
        $this->assertStringContainsString('Sumber HP', $message);
        $this->assertStringNotContainsString('Anugerah', $message);
        $this->get('/t/'.$order->tracking_token)->assertOk()->assertSee('Sumber HP')->assertDontSee('Anugerah');
    }

    public function test_documents_whatsapp_and_public_tracking_use_the_owner_store(): void
    {
        [$a, $tech, , $own] = $this->shop('A');
        [$b, , , $other] = $this->shop('B');
        $this->context($a);
        $this->actingAs($a);
        app(ManageServiceOrder::class)->assign($own->id, $tech->id);
        $own->refresh();
        app(ManageServiceOrder::class)->invoice($own->id, ['rows' => [['type' => 'part', 'description' => 'Part A', 'qty' => 1, 'price' => 123000, 'cost' => 99887]], 'warranty_days' => 0, 'unrepairable' => false], false, InvoiceLines::revision($own));
        $this->get(route('tts.print', $own->id))->assertOk()->assertSee('Identitas A')->assertDontSee('Identitas B')->assertDontSee('99.887');
        foreach (['tts.receipt.pdf', 'tts.pdf'] as $route) {
            $this->get(route($route, $own->id))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        }
        $message = rawurldecode($this->get(route('tts.whatsapp', $own->id))->headers->get('Location'));
        $this->assertStringContainsString('Identitas A', $message);
        $this->assertStringNotContainsString('Identitas B', $message);
        $this->actingAs($b);
        Tenant::whereKey($a->tenant_id)->update(['is_active' => false]);
        app(TenantContext::class)->forget();
        $this->get('/t/'.$own->tracking_token)->assertOk()->assertSee('Identitas A')->assertSee('Kategori A')->assertSee('Part A')->assertDontSee('Identitas B')->assertDontSee($own->customer_name)->assertDontSee('PRIVATE ADDRESS')->assertDontSee('99887')->assertDontSee('6281234567890?');
        $this->context($b);
        $data = app(Document::class)->order($own->id, true, true, $own->tracking_token);
        $this->assertSame('Identitas A', $data['store']->name);
        $this->assertNull($data['customer']);
        $this->assertNull($data['phone']);
        $this->assertSame('Teknisi A', $data['technician']);
        $this->assertFalse($data['items']->first()->offsetExists('cost'));
        $this->assertSame($b->tenant_id, app(TenantContext::class)->id());
        app(TenantContext::class)->forget();
        $this->get(route('tracking.pdf', $own->tracking_token))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($a);
        $this->get('/t/'.$own->tracking_token)->assertOk()->assertSee('Identitas A');
        $this->get(route('tracking.pdf', $own->tracking_token))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get(route('tts.whatsapp', $own->id))->assertForbidden();
        $a->update(['is_active' => false]);
        $this->actingAs($a);
        $this->get('/t/'.$own->tracking_token)->assertOk()->assertSee('Identitas A');
        $this->get(route('tracking.pdf', $own->tracking_token))->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->get('/t/'.str_repeat('x', 64))->assertNotFound();
        $this->get(route('tracking.pdf', $other->tracking_token))->assertNotFound();
    }
}
