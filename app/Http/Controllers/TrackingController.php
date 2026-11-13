<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

class TrackingController extends Controller
{
    public function show(string $token): Response
    {
        $order = preg_match('/\A[a-zA-Z0-9]{64}\z/', $token)
            ? ServiceOrder::query()->select(['id', 'number', 'category_id', 'item_description', 'complaint', 'status', 'created_at', 'assigned_at', 'invoiced_on', 'picked_up_on', 'cancelled_at', 'warranty_days', 'warranty_until', 'unrepairable', 'tracking_token'])->with('category:id,name')->where('tracking_token', $token)->first()
            : null;
        abort_unless($order && hash_equals($order->tracking_token, $token), 404, '', ['X-Robots-Tag' => 'noindex']);

        $items = $order->invoiced_on ? $order->items()->orderBy('id')->get(['description', 'qty', 'price']) : collect();
        $store = StoreSetting::query()->first(['name', 'address', 'phone']);

        return response()->view('tracking.show', compact('order', 'items', 'store'))->header('X-Robots-Tag', 'noindex')->header('Cache-Control', 'private, no-store');
    }

    public function whatsapp(int $id): RedirectResponse
    {
        $order = ServiceOrder::query()->select(['id', 'number', 'customer_name', 'customer_phone', 'category_id', 'item_description', 'invoiced_on', 'warranty_days', 'warranty_until', 'tracking_token'])->with('category:id,name')->findOrFail($id);
        $storeName = StoreSetting::query()->value('name') ?: 'Anugerah';
        $link = rtrim(config('app.url'), '/').'/t/'.$order->tracking_token;
        $message = $order->invoiced_on
            ? WhatsApp::invoiceMessage($order->customer_name, $order->number, $order->items()->get(['qty', 'price'])->sum(fn ($item) => $item->qty * $item->price), $order->warranty_days && $order->warranty_until ? Carbon::parse($order->warranty_until)->format('d M Y') : null, $link, $storeName)
            : WhatsApp::receiptMessage($order->customer_name, $order->category->name, $order->item_description, $order->number, $link, $storeName);

        return redirect()->away(WhatsApp::url($order->customer_phone, $message));
    }
}
