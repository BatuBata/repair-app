<div class="sales-page">
    @include('livewire.sales.styles')
    <div class="title-row"><div><h1>Penjualan</h1><p class="sub">Transaksi barang jualan</p></div><a class="btn primary desktop-only" href="{{ route('sales.create') }}">Buat penjualan</a></div>
    @if(session('sale-status'))<x-ui.notice type="success">{{ session('sale-status') }}</x-ui.notice>@endif
    <x-ui.index-search label="Cari penjualan" placeholder="Cari nomor, nama, atau HP" :total="$sales->total()" unit="penjualan" wire:model.live.debounce.300ms="search" />
    <table class="table money-first"><thead><tr><th class="act">Aksi</th><th>Nomor</th><th>Customer</th><th>Tanggal jual</th><th>Barang</th><th class="n">Total</th></tr></thead><tbody>
    @forelse($sales as $sale)
        <tr wire:key="sale-{{ $sale->id }}"><td class="act"><a class="btn sm" href="{{ route('sales.show', $sale->id) }}" aria-label="Lihat {{ $sale->number }}">Lihat</a></td><td class="c-id sale-number">{{ $sale->number }}</td><td class="c-who">{{ ucwords($sale->customer_name ?? '') ?: 'Customer umum' }}</td><td class="c-meta">{{ $sale->sold_on->format('d/m/Y') }}</td><td class="c-what">{{ $sale->items->count() }} barang</td><td class="n c-amt">Rp{{ number_format($sale->items->sum(fn ($item) => $item->subtotalPrice()) - $sale->discount, 0, ',', '.') }}</td></tr>
    @empty
        <tr><td colspan="6" class="empty-cell"><div class="empty"><strong>{{ $search !== '' ? 'Tidak ada penjualan yang cocok.' : 'Belum ada penjualan.' }}</strong><p>Buat penjualan untuk mencatat barang yang terjual.</p></div></td></tr>
    @endforelse
    </tbody></table>
    {{ $sales->onEachSide(1)->links('components.ui.pagination', ['livewire' => true]) }}
    <div class="actionbar"><a class="btn primary" href="{{ route('sales.create') }}">Buat penjualan</a></div>
</div>
