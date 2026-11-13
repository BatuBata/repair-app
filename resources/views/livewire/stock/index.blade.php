<div class="stock-page">
    @include('livewire.stock.styles')
    <div class="title-row"><div><h1>Stok</h1><p class="sub">Barang jualan dan jumlah yang tersedia</p></div><a class="btn primary desktop-only" href="{{ route('stock.create') }}">Tambah barang</a></div>
    @if($status || session('stock-status'))<p class="notice" role="status">{{ $status ?: session('stock-status') }}</p>@endif
    <table class="table stock-list"><thead><tr><th class="act">Aksi</th><th>Nama barang</th><th class="n price-col">Harga beli</th><th class="n price-col">Harga jual</th><th class="n qty-col">Stok</th><th class="status-col">Status</th></tr></thead><tbody>
    @forelse($products as $product)
        <tr wire:key="product-{{ $product->id }}"><td class="act"><x-ui.row-actions :id="'product-actions-'.$product->id" :label="$product->name">
            <a role="menuitem" href="{{ route('stock.edit', $product->id) }}"><x-ui.icon name="settings" />Ubah barang</a>
            <a role="menuitem" href="{{ route('stock.restock', $product->id) }}"><x-ui.icon name="plus" />Restok &amp; riwayat</a>
            <button class="danger" type="button" role="menuitem" onclick="if (confirm(@js(($product->is_active ? 'Nonaktifkan' : 'Aktifkan').' barang '.$product->name.'?'))) Livewire.find(@js($this->getId())).toggleActive({{ $product->id }})"><x-ui.icon name="ban" />{{ $product->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
        </x-ui.row-actions></td>
        <td class="stock-name">{{ $product->name }}<span class="stock-mobile">Beli Rp{{ number_format($product->cost_price, 0, ',', '.') }} · Jual Rp{{ number_format($product->sell_price, 0, ',', '.') }}</span></td>
        <td class="n desktop-cell">Rp{{ number_format($product->cost_price, 0, ',', '.') }}</td><td class="n desktop-cell">Rp{{ number_format($product->sell_price, 0, ',', '.') }}</td><td class="n stock-count">{{ number_format($product->stock, 0, ',', '.') }}<span class="stock-mobile">unit</span></td><td class="stock-state"><span class="pill">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></td></tr>
    @empty
        <tr><td colspan="6" class="empty-cell"><div class="empty"><strong>Belum ada barang jualan.</strong><p>Tambah barang untuk mulai mencatat stok.</p></div></td></tr>
    @endforelse
    </tbody></table>
    <div class="actionbar"><a class="btn primary" href="{{ route('stock.create') }}">Tambah barang</a></div>
</div>
