<?php

namespace App\Http\Controllers;

use App\Models\ServiceOrder;
use App\Models\User;
use App\Services\Documents\Document;
use Illuminate\Http\Response;

class DocumentController extends Controller
{
    public function receipt(int $id, Document $document): Response
    {
        $user = User::activeAccount();

        return $document->respond($document->order($id, false, $user->role->value === 'admin'), 'html');
    }

    public function receiptPdf(int $id, Document $document): Response
    {
        $user = User::activeAccount();

        return $document->respond($document->order($id, false, $user->role->value === 'admin'), 'thermal');
    }

    public function orderPdf(int $id, Document $document): Response
    {
        User::activeAccount(admin: true);

        return $document->respond($document->order($id, false, true), 'a5');
    }

    public function publicInvoice(string $token, Document $document): Response
    {
        $order = preg_match('/\A[a-zA-Z0-9]{64}\z/', $token)
            ? ServiceOrder::query()->select(['id', 'tracking_token', 'invoiced_on'])->where('tracking_token', $token)->first() : null;
        abort_unless($order && hash_equals($order->tracking_token, $token) && $order->invoiced_on, 404, '', ['X-Robots-Tag' => 'noindex']);

        return $document->respond($document->order($order->id, true, true, $token), 'a5')->header('X-Robots-Tag', 'noindex');
    }

    public function saleReceipt(int $id, Document $document): Response
    {
        User::activeAccount(admin: true);

        return $document->respond($document->sale($id), 'html');
    }

    public function saleReceiptPdf(int $id, Document $document): Response
    {
        User::activeAccount(admin: true);

        return $document->respond($document->sale($id), 'thermal');
    }

    public function salePdf(int $id, Document $document): Response
    {
        User::activeAccount(admin: true);

        return $document->respond($document->sale($id), 'a5');
    }
}
