<div class="sales-page record-detail">
    @include('livewire.sales.styles')
    @include('livewire.detail-styles')
    @php($totalPrice = $sale->items->sum(fn ($item) => $item->subtotalPrice()))
    @php($totalCost = $sale->items->sum(fn ($item) => $item->subtotalCost()))
    <a class="crumb" href="{{ route('sales.index') }}"><x-ui.icon name="arrow-left" />Penjualan</a>
    <header class="title-row"><div>
        <div class="detail-heading"><h1 class="detail-number">{{ $sale->number }}</h1><span class="pill">{{ $sale->paid_at ? 'Pembayaran tercatat' : 'Pembayaran belum dicatat' }}</span></div>
        <p class="sub">Penjualan {{ $sale->sold_on->format('d/m/Y') }} · dicatat oleh {{ $sale->creator->name }}</p>
    </div><x-ui.row-actions id="sale-detail-actions" :label="$sale->number">
        <a role="menuitem" href="{{ route('sales.print', $sale->id) }}"><x-ui.icon name="printer" />Cetak struk</a>
        <a role="menuitem" href="{{ route('sales.receipt.pdf', $sale->id) }}"><x-ui.icon name="download" />PDF struk</a>
        <a role="menuitem" href="{{ route('sales.pdf', $sale->id) }}"><x-ui.icon name="file-text" />PDF A5</a>
        <button role="menuitem" class="danger" type="button" onclick="Livewire.find(@js($this->getId())).confirmDelete()"><x-ui.icon name="trash-2" />Hapus penjualan</button>
    </x-ui.row-actions></header>
    @if(session('sale-status'))<p class="notice" role="status">{{ session('sale-status') }}</p>@endif
    @error('sale')<p class="notice error-text" role="alert">{{ $message }}</p>@enderror
    <section class="detail-overview" aria-label="Pelanggan dan pembayaran">
        <div class="detail-fact"><h2><x-ui.icon name="user" />Pelanggan</h2><p class="detail-value">{{ $sale->customer_name ?: 'Customer umum' }}</p><p class="detail-meta">{{ $sale->customer_phone ?: 'Nomor HP belum dicatat' }}</p></div>
        <div class="detail-fact"><h2><x-ui.icon name="circle-check" />Pembayaran</h2><p class="detail-value">{{ $sale->paid_at ? \App\Support\Payment::label($sale->payment_method, $sale->payment_bank) : 'Belum dicatat' }}</p></div>
    </section>
    <div class="detail-layout">
        <section class="detail-panel" aria-labelledby="sale-items-title"><h2 id="sale-items-title">Barang yang dibeli</h2>
            <table class="table line-items"><thead><tr><th>Barang / jumlah</th><th class="n">Harga per unit</th><th class="n">Subtotal</th></tr></thead><tbody>
            @foreach($sale->items as $item)
                <tr><td class="detail-item-name"><strong>{{ $item->product_name }}</strong><span class="detail-meta">{{ number_format($item->qty, 0, ',', '.') }} unit</span></td><td class="n detail-item-prices">Rp{{ number_format($item->price, 0, ',', '.') }}<span class="detail-meta">Modal per unit Rp{{ number_format($item->cost, 0, ',', '.') }}</span></td><td class="n detail-item-subtotal"><span class="mobile-label">Subtotal </span>Rp{{ number_format($item->subtotalPrice(), 0, ',', '.') }}</td></tr>
            @endforeach
            </tbody></table>
        </section>
        <aside class="detail-panel" aria-labelledby="sale-summary-title"><h2 id="sale-summary-title">Ringkasan penjualan</h2>
            <dl class="detail-total"><dt>Total penjualan</dt><dd>Rp{{ number_format($totalPrice, 0, ',', '.') }}</dd></dl>
            <dl class="detail-totals"><div><dt>Total modal</dt><dd>Rp{{ number_format($totalCost, 0, ',', '.') }}</dd></div><div><dt>Laba kotor</dt><dd>Rp{{ number_format($totalPrice - $totalCost, 0, ',', '.') }}</dd></div></dl>
            <div class="detail-warranty"><h3>Garansi</h3><p>{{ $sale->warranty_days ? $sale->warranty_days.' hari · berlaku sampai '.$sale->warranty_until->format('d/m/Y') : 'Tanpa garansi' }}</p></div>
        </aside>
    </div>
    @if($confirmingDelete)
        <dialog class="sheet-dialog" x-init="$el.showModal()" x-on:cancel="$wire.cancelDelete()" aria-labelledby="delete-sale-title"><div class="dialog-body"><h2 id="delete-sale-title">Hapus penjualan?</h2><p>{{ $sale->number }} akan dihapus dan stok seluruh barang dikembalikan. Tindakan ini tidak dapat dibatalkan.</p><div class="dialog-actions"><button class="btn ghost" type="button" wire:click="cancelDelete" x-on:click="$el.closest('dialog').close()">Batal</button><button class="btn danger" type="button" wire:click="delete" wire:loading.attr="disabled" wire:target="delete">Ya, hapus penjualan</button></div></div></dialog>
    @endif
</div>
