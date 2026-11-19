<?php

namespace App\Http\Controllers;

use App\Enums\ServiceOrderStatus;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Models\Tenant;
use App\Support\TenantContext;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class TrackingController extends Controller
{
    public function show(string $token): Response
    {
        $order = preg_match('/\A[a-zA-Z0-9]{64}\z/', $token)
            ? ServiceOrder::withoutGlobalScope('tenant')->select(['id', 'tenant_id', 'number', 'category_id', 'item_description', 'complaint', 'status', 'created_at', 'assigned_at', 'inspection_started_at', 'work_started_at', 'invoiced_on', 'picked_up_on', 'cancelled_at', 'cancel_reason', 'warranty_days', 'warranty_until', 'unrepairable', 'discount', 'tracking_token'])->where('tracking_token', $token)->first()
            : null;
        abort_unless($order && hash_equals($order->tracking_token, $token), 404, '', ['X-Robots-Tag' => 'noindex']);

        $order->load(['category' => fn ($query) => $query->withoutGlobalScope('tenant')->where('tenant_id', $order->tenant_id)->select(['id', 'name'])]);
        $items = $order->invoiced_on ? $order->items()->orderBy('id')->get(['description', 'qty', 'price']) : collect();
        $store = StoreSetting::withoutGlobalScope('tenant')->where('tenant_id', $order->tenant_id)->first(['name', 'address', 'phone']);

        $storeName = $store?->name ?: Tenant::findOrFail($order->tenant_id)->name;

        return response()->view('tracking.show', compact('order', 'items', 'store', 'storeName'))->header('X-Robots-Tag', 'noindex')->header('Cache-Control', 'private, no-store');
    }

    public function whatsapp(Request $request, int $id): RedirectResponse
    {
        $order = ServiceOrder::query()->select(['id', 'number', 'customer_name', 'customer_phone', 'category_id', 'item_description', 'inspection_result', 'action_plan', 'status', 'cancel_reason', 'invoiced_on', 'discount', 'unrepairable', 'warranty_days', 'warranty_until', 'tracking_token'])->with('category:id,name')->findOrFail($id);
        $storeName = StoreSetting::query()->value('name') ?: app(TenantContext::class)->tenant()->name;
        $link = rtrim(config('app.url'), '/').'/t/'.$order->tracking_token;
        if ($order->getAttribute('status') === ServiceOrderStatus::Batal) {
            return redirect()->away(WhatsApp::url($order->customer_phone, WhatsApp::cancellationMessage($order->customer_name, $order->number, $order->cancel_reason, $link, $storeName)));
        }
        if ($request->boolean('inspection')) {
            abort_unless($order->inspectionNotes() !== '', 422, 'Simpan hasil pengecekan dan rencana tindakan terlebih dahulu.');

            return redirect()->away(WhatsApp::url($order->customer_phone, WhatsApp::inspectionMessage($order->customer_name, $order->number, $order->inspectionNotes(), $link, $storeName)));
        }
        $message = $order->invoiced_on
            ? WhatsApp::invoiceMessage($order->customer_name, $order->number, $order->items()->get(['qty', 'price'])->sum(fn ($item) => $item->qty * $item->price) - $order->discount, $order->warranty_days && $order->warranty_until ? Carbon::parse($order->warranty_until)->format('d M Y') : null, $link, $storeName, $order->unrepairable)
            : WhatsApp::receiptMessage($order->customer_name, $order->category->name, $order->item_description, $order->number, $link, $storeName);

        return redirect()->away(WhatsApp::url($order->customer_phone, $message));
    }
}
