<?php

namespace App\Services\Documents;

use App\Models\Sale;
use App\Models\ServiceOrder;
use App\Models\StoreSetting;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class Document
{
    /** @return array<string, mixed> */
    public function order(int $id, bool $public, bool $invoiceAllowed, ?string $token = null): array
    {
        $fields = ['id', 'number', 'category_id', 'item_description', 'complaint', 'created_at', 'invoiced_on', 'technician_id', 'warranty_days', 'warranty_until', 'unrepairable', 'tracking_token'];
        if (! $public) {
            $fields = [...$fields, 'customer_name', 'customer_phone'];
        }
        $order = ServiceOrder::query()->select($fields)->with(['category:id,name', 'technician:id,name'])->findOrFail($id);
        if ($public) {
            abort_unless($token !== null && hash_equals($order->tracking_token, $token) && $order->invoiced_on, 404, '', ['X-Robots-Tag' => 'noindex']);
        }
        $invoice = $invoiceAllowed && $order->invoiced_on !== null;
        $items = $invoice ? $order->items()->orderBy('id')->get(['description', 'qty', 'price']) : collect();
        $qr = (new Writer(new ImageRenderer(new RendererStyle(256, 4), new SvgImageBackEnd)))->writeString(rtrim(config('app.url'), '/').'/t/'.$order->tracking_token);

        return ['kind' => $invoice ? 'Nota servis' : 'Tanda terima servis', 'number' => $order->number, 'date' => $order->created_at, 'invoiceDate' => $invoice ? $order->invoiced_on : null,
            'customer' => $public ? null : $order->customer_name, 'phone' => $public ? null : $order->customer_phone,
            'item' => trim($order->category->name.' '.$order->item_description), 'complaint' => $order->complaint,
            'technician' => $invoice ? $order->technician?->name : null, 'items' => $items, 'warrantyDays' => $invoice ? $order->warranty_days : null,
            'warrantyUntil' => $invoice ? $order->warranty_until : null, 'unrepairable' => $invoice && $order->unrepairable,
            'qr' => 'data:image/svg+xml;base64,'.base64_encode($qr), 'public' => $public];
    }

    /** @return array<string, mixed> */
    public function sale(int $id): array
    {
        $sale = Sale::query()->select(['id', 'number', 'sold_on', 'customer_name', 'customer_phone', 'warranty_days', 'warranty_until'])->findOrFail($id);
        $items = $sale->items()->orderBy('id')->get(['product_name', 'qty', 'price'])->map(fn ($item) => (object) ['description' => $item->product_name, 'qty' => $item->qty, 'price' => $item->price]);

        return ['kind' => 'Penjualan', 'number' => $sale->number, 'date' => $sale->sold_on, 'invoiceDate' => null,
            'customer' => $sale->customer_name, 'phone' => $sale->customer_phone, 'item' => null, 'complaint' => null, 'technician' => null,
            'items' => $items, 'warrantyDays' => $sale->warranty_days, 'warrantyUntil' => $sale->warranty_until, 'unrepairable' => false, 'qr' => null, 'public' => false];
    }

    /** @param array<string, mixed> $data */
    public function respond(array $data, string $format): Response
    {
        $store = StoreSetting::query()->first(['name', 'address', 'phone', 'logo_path', 'warranty_terms']);
        $logo = null;
        if ($store?->logo_path && Storage::disk('public')->exists($store->logo_path)) {
            $mime = Storage::disk('public')->mimeType($store->logo_path);
            if (in_array($mime, ['image/png', 'image/jpeg', 'image/gif', 'image/webp'], true)) {
                $logo = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($store->logo_path));
            }
        }
        $data += ['store' => $store, 'logo' => $logo, 'format' => $format];
        if ($format === 'html') {
            return response()->view('documents.document', $data)->header('Cache-Control', 'private, no-store');
        }
        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setChroot(public_path());
        $options->setFontDir(storage_path('framework/cache'));
        $options->setFontCache(storage_path('framework/cache'));
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('documents.document', $data)->render());
        $pdf->setPaper($format === 'a5' ? 'a5' : [0, 0, 58 * 72 / 25.4, 14000]);
        $height = 0;
        $headerBottom = 0;
        $pdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$height, &$headerBottom) {
            $node = $frame->get_node();
            if (in_array($node->nodeName, ['#text', 'img'], true)) {
                $position = $frame->get_position();
                $bottom = $position['y'] + $frame->get_margin_height();
                $height = max($height, $bottom);
                for ($parent = $node->parentNode; $parent; $parent = $parent->parentNode) {
                    if ($parent->nodeName === 'header') {
                        $headerBottom = max($headerBottom, $bottom);
                        break;
                    }
                }
            }
        }]]);
        $pdf->render();
        if ($format === 'thermal') {
            if ($pdf->getCanvas()->get_page_count() === 1 && $height + 50 < 14000) {
                $pdf = new Dompdf($options);
                $pdf->loadHtml(view('documents.document', $data)->render());
                $pdf->setPaper([0, 0, 58 * 72 / 25.4, max(100, $height + 50)]);
                $pdf->render();
            }
        } else {
            $data['headerTop'] = max(39, ceil(($headerBottom + 12) * 25.4 / 72));
            if ($data['headerTop'] > 39) {
                $pdf = new Dompdf($options);
                $pdf->loadHtml(view('documents.document', $data)->render());
                $pdf->setPaper('a5');
                $pdf->render();
            }
            $pdf->getCanvas()->page_text(360, 580, '{PAGE_NUM} / {PAGE_COUNT}', $pdf->getFontMetrics()->getFont('Plex'), 8);
        }

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="dokumen.pdf"', 'Cache-Control' => 'private, no-store']);
    }
}
