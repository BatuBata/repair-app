<div class="stock-page record-detail">
    @include('livewire.stock.styles')
    @include('livewire.detail-styles')
    <a class="crumb" href="{{ route('stock.index') }}"><x-ui.icon name="arrow-left" />Stok barang</a>
    <header class="title-row"><div><div class="detail-heading"><h1>{{ $product->name }}</h1><span class="pill">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></div><p class="sub">Restok barang dan riwayat stok masuk</p></div><x-ui.row-actions id="stock-detail-actions" :label="$product->name"><a role="menuitem" href="{{ route('stock.edit', $product->id) }}"><x-ui.icon name="pencil" />Ubah barang</a></x-ui.row-actions></header>
    <section class="detail-overview three-facts" aria-label="Stok dan harga barang">
        <div class="detail-fact"><h2><x-ui.icon name="package" />Stok saat ini</h2><p class="detail-value">{{ number_format($product->stock, 0, ',', '.') }} unit</p></div>
        <div class="detail-fact"><h2>Harga beli per unit</h2><p class="detail-value">Rp{{ number_format($product->cost_price, 0, ',', '.') }}</p></div>
        <div class="detail-fact"><h2>Harga jual per unit</h2><p class="detail-value">Rp{{ number_format($product->sell_price, 0, ',', '.') }}</p></div>
    </section>
    @if($status)<p class="notice" role="status">{{ $status }}</p>@endif
    @if(! $product->is_active)<p class="notice">Barang nonaktif. Aktifkan melalui menu barang di daftar stok sebelum restok.</p>@endif
    @if($errors->any())<p class="notice" role="alert" x-init="$nextTick(() => $root.querySelector('[aria-invalid=true]')?.focus())">Periksa kembali isian di bawah.</p>@endif
    <div class="detail-layout stock-layout"><section class="detail-panel restock-panel" aria-labelledby="restock-form-title"><h2 id="restock-form-title">Tambah stok</h2>
    <form class="stock-form" wire:submit="save" novalidate>
        <div><x-ui.field name="qty" label="Jumlah masuk (unit)" wire:model="qty" inputmode="numeric" required aria-invalid="{{ $errors->has('qty') ? 'true' : 'false' }}" :aria-describedby="$errors->has('qty') ? 'qty-error' : null" />@error('qty')<p class="error-text" id="qty-error">{{ $message }}</p>@enderror</div>
        <div><x-ui.money name="cost_price" label="Harga beli baru per unit" :value="$cost_price" x-on:input="$wire.cost_price = $el.value.replace(/\D/g, '')" required aria-invalid="{{ $errors->has('cost_price') ? 'true' : 'false' }}" :aria-describedby="$errors->has('cost_price') ? 'cost_price-error' : null" />@error('cost_price')<p class="error-text" id="cost_price-error">{{ $message }}</p>@enderror</div>
        <div><x-ui.field name="entry_date" label="Tanggal masuk" type="date" :value="$entry_date" wire:model="entry_date" required aria-invalid="{{ $errors->has('entry_date') ? 'true' : 'false' }}" :aria-describedby="$errors->has('entry_date') ? 'entry_date-error' : null" />@error('entry_date')<p class="error-text" id="entry_date-error">{{ $message }}</p>@enderror</div>
        <div><x-ui.field name="note" label="Catatan (opsional)" wire:model="note" maxlength="1000" aria-invalid="{{ $errors->has('note') ? 'true' : 'false' }}" :aria-describedby="$errors->has('note') ? 'note-error' : null" />@error('note')<p class="error-text" id="note-error">{{ $message }}</p>@enderror</div>
        <p class="sub">Jumlah stok dan harga beli akan diperbarui. Restok bukan pengeluaran.</p>
        <div class="form-actions"><button class="btn primary desktop-only" type="submit" wire:loading.attr="disabled" wire:target="save"><x-ui.icon name="plus" /><span wire:loading.remove wire:target="save">Simpan restok</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
        <div class="actionbar"><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="save"><x-ui.icon name="plus" /><span wire:loading.remove wire:target="save">Simpan restok</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
    </form></section>
    <section class="detail-panel history" aria-labelledby="stock-history-title"><div class="section-head"><h2 id="stock-history-title">Riwayat restok</h2><span class="meta">{{ $entries->count() }} catatan</span></div>
        <table class="table"><thead><tr><th>Tanggal masuk</th><th class="n">Jumlah</th><th class="n">Harga beli per unit</th><th>Dicatat oleh</th></tr></thead><tbody>
        @forelse($entries as $entry)
            <tr wire:key="entry-{{ $entry->id }}"><td class="history-date">{{ $entry->entry_date->format('d/m/Y') }}@if($entry->note)<span class="history-note">{{ $entry->note }}</span>@endif</td><td class="n history-qty">+{{ number_format($entry->qty, 0, ',', '.') }} unit</td><td class="n history-cost">Rp{{ number_format($entry->cost_price, 0, ',', '.') }}</td><td class="history-by">{{ $entry->creator->name }}</td></tr>
        @empty
            <tr><td colspan="4" class="empty-cell"><div class="empty">Belum ada riwayat restok untuk barang ini.</div></td></tr>
        @endforelse
        </tbody></table>
    </section></div>
</div>
