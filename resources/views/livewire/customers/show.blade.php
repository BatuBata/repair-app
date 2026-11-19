<div class="record-detail">
    @include('livewire.detail-styles')
    <a class="crumb" href="{{ route('customers.index') }}"><x-ui.icon name="arrow-left" />Pelanggan</a>
    <header class="title-row"><div><h1>{{ $customer->name }}</h1><p class="sub">Data pelanggan dan riwayat transaksi di toko ini</p></div></header>
    <section class="detail-overview" aria-label="Kontak dan alamat pelanggan">
        <div class="detail-fact"><h2><x-ui.icon name="user" />Nomor HP / WhatsApp</h2><p class="detail-value">{{ $customer->phone }}</p></div>
        <div class="detail-fact"><h2><x-ui.icon name="store" />Alamat</h2><p class="detail-meta" style="white-space:pre-line">{{ $customer->address ?: 'Alamat belum diisi.' }}</p></div>
    </section>
    <section class="detail-panel" aria-labelledby="customer-history-title">
        <div class="section-head"><h2 id="customer-history-title">Riwayat servis</h2><span class="meta">{{ $orders->total() }} TTS</span></div>
        <p class="small muted customer-note">Transaksi yang terhubung tetap tampil meskipun nomor HP berubah. Data lama tanpa relasi dicocokkan berdasarkan nomor HP saat ini.</p>
        <table class="table history-orders"><thead><tr><th class="act">Aksi</th><th>Nomor / tanggal masuk</th><th>Barang / keluhan</th><th>Status</th></tr></thead><tbody>
            @forelse($orders as $order)
                <tr wire:key="customer-order-{{ $order->id }}">
                    <td class="act"><a class="btn detail-view-link" href="{{ route('tts.show', $order->id) }}" aria-label="Lihat {{ $order->number }}"><x-ui.icon name="eye" /></a></td>
                    <td class="history-number"><strong class="id">{{ $order->number }}</strong><div class="small muted">{{ $order->created_at->timezone('Asia/Makassar')->format('d/m/Y') }}</div></td>
                    <td class="history-body">{{ $order->category?->name ?: '—' }}{{ $order->item_description ? ' · '.$order->item_description : '' }}<div class="small muted">{{ $order->complaint }}</div></td>
                    <td class="history-state"><x-ui.status :status="$order->status->value" /></td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="empty">Belum ada riwayat servis pelanggan ini.</div></td></tr>
            @endforelse
        </tbody></table>
        {{ $orders->links() }}
    </section>
    <section class="detail-panel" aria-labelledby="customer-sales-title">
        <div class="section-head"><h2 id="customer-sales-title">Riwayat penjualan</h2><span class="meta">{{ $sales->total() }} transaksi</span></div>
        <table class="table history-sales"><thead><tr><th class="act">Aksi</th><th>Nomor / tanggal jual</th><th>Barang</th></tr></thead><tbody>
            @forelse($sales as $sale)
                <tr wire:key="customer-sale-{{ $sale->id }}">
                    <td class="act"><a class="btn detail-view-link" href="{{ route('sales.show', $sale->id) }}" aria-label="Lihat {{ $sale->number }}"><x-ui.icon name="eye" /></a></td>
                    <td class="history-number"><strong class="id">{{ $sale->number }}</strong><div class="small muted">{{ $sale->sold_on->format('d/m/Y') }}</div></td>
                    <td class="history-body">@foreach($sale->items as $item)<div>{{ $item->product_name }} × {{ $item->qty }}</div>@endforeach</td>
                </tr>
            @empty
                <tr><td colspan="3"><div class="empty">Belum ada riwayat penjualan pelanggan ini.</div></td></tr>
            @endforelse
        </tbody></table>
        {{ $sales->links() }}
    </section>
</div>
