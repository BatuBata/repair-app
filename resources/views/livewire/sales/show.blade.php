<div class="sales-page">
    @include('livewire.sales.styles')
    @php($totalPrice = $sale->items->sum(fn ($item) => $item->subtotalPrice()))
    @php($totalCost = $sale->items->sum(fn ($item) => $item->subtotalCost()))
    <div class="title-row"><div><h1 class="sale-number">{{ $sale->number }}</h1><p class="sub">Penjualan · {{ $sale->sold_on->format('d/m/Y') }}</p></div></div>
    @if(session('sale-status'))<p class="notice" role="status">{{ session('sale-status') }}</p>@endif
    @error('sale')<p class="notice error-text" role="alert">{{ $message }}</p>@enderror
    <div class="sale-information"><p><strong>{{ $sale->customer_name ?: 'Customer umum' }}</strong></p>@if($sale->customer_phone)<p>{{ $sale->customer_phone }}</p>@endif<p class="sub">Dicatat oleh {{ $sale->creator->name }}</p><p>{{ $sale->warranty_days ? 'Garansi '.$sale->warranty_days.' hari · berlaku sampai '.$sale->warranty_until->format('d/m/Y') : 'Tanpa garansi' }}</p></div>
    <table class="table sale-items"><thead><tr><th>Barang</th><th class="n">Jumlah</th><th class="n">Harga jual</th><th class="n">Modal per unit</th><th class="n">Subtotal</th></tr></thead><tbody>
    @foreach($sale->items as $item)
        <tr><td class="item-name">{{ $item->product_name }}</td><td class="n item-qty">{{ number_format($item->qty, 0, ',', '.') }} unit</td><td class="n item-price"><span class="mobile-label">Harga jual</span>Rp{{ number_format($item->price, 0, ',', '.') }}</td><td class="n item-cost"><span class="mobile-label">Modal per unit</span>Rp{{ number_format($item->cost, 0, ',', '.') }}</td><td class="n item-subtotal"><span class="mobile-label">Subtotal</span>Rp{{ number_format($item->subtotalPrice(), 0, ',', '.') }}</td></tr>
    @endforeach
    </tbody></table>
    <div class="sale-summary"><div class="summary-line total"><span>Total penjualan</span><strong>Rp{{ number_format($totalPrice, 0, ',', '.') }}</strong></div><div class="summary-line"><span>Total modal</span><span>Rp{{ number_format($totalCost, 0, ',', '.') }}</span></div><div class="summary-line"><span>Laba kotor</span><strong>Rp{{ number_format($totalPrice - $totalCost, 0, ',', '.') }}</strong></div></div>
    <div class="detail-actions"><a class="btn" href="{{ route('sales.index') }}">Kembali ke penjualan</a><button class="btn danger" type="button" wire:click="confirmDelete">Hapus penjualan</button></div>
    @if($confirmingDelete)
        <dialog class="sheet-dialog" x-init="$el.showModal()" x-on:cancel="$wire.cancelDelete()" aria-labelledby="delete-sale-title"><div class="dialog-body"><h2 id="delete-sale-title">Hapus penjualan?</h2><p>{{ $sale->number }} akan dihapus dan stok seluruh barang dikembalikan. Tindakan ini tidak dapat dibatalkan.</p><div class="dialog-actions"><button class="btn ghost" type="button" wire:click="cancelDelete" x-on:click="$el.closest('dialog').close()">Batal</button><button class="btn danger" type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Ya, hapus penjualan</button></div></div></dialog>
    @endif
</div>
