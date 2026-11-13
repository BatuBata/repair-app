<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/t/{token}/pdf', [DocumentController::class, 'publicInvoice'])->name('tracking.pdf');
Route::middleware('auth')->group(function () {
    Route::get('/tts/{id}/struk', [DocumentController::class, 'receipt'])->whereNumber('id')->name('tts.print');
    Route::get('/tts/{id}/struk.pdf', [DocumentController::class, 'receiptPdf'])->whereNumber('id')->name('tts.receipt.pdf');
    Route::get('/tts/{id}/pdf', [DocumentController::class, 'orderPdf'])->whereNumber('id')->name('tts.pdf');
    Route::get('/penjualan/{id}/struk', [DocumentController::class, 'saleReceipt'])->whereNumber('id')->name('sales.print');
    Route::get('/penjualan/{id}/struk.pdf', [DocumentController::class, 'saleReceiptPdf'])->whereNumber('id')->name('sales.receipt.pdf');
    Route::get('/penjualan/{id}/pdf', [DocumentController::class, 'salePdf'])->whereNumber('id')->name('sales.pdf');
});
