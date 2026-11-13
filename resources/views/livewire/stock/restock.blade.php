<div class="stock-page">
    @include('livewire.stock.styles')
    <div class="title-row"><div><h1>Restok barang</h1><p class="sub">{{ $product->name }}</p></div></div>
    <div class="stock-summary"><span>Stok saat ini <strong>{{ number_format($product->stock, 0, ',', '.') }} unit</strong></span><span>Harga beli <strong>Rp{{ number_format($product->cost_price, 0, ',', '.') }}</strong></span><span class="pill">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span></div>
    @if($status)<p class="notice" role="status">{{ $status }}</p>@endif
    @if(! $product->is_active)<p class="notice">Barang nonaktif. Aktifkan melalui menu barang di daftar stok sebelum restok.</p>@endif
    @if($errors->any())<p class="notice" role="alert" x-init="$nextTick(() => $root.querySelector('[aria-invalid=true]')?.focus())">Periksa kembali isian di bawah.</p>@endif
    <form class="stock-form" wire:submit="save" novalidate>
        <div><x-ui.field name="qty" label="Jumlah masuk (unit)" wire:model="qty" inputmode="numeric" required aria-invalid="{{ $errors->has('qty') ? 'true' : 'false' }}" aria-describedby="qty-error" />@error('qty')<p class="error-text" id="qty-error">{{ $message }}</p>@enderror</div>
        <div><x-ui.money name="cost_price" label="Harga beli baru per unit" :value="$cost_price" x-on:input="$wire.cost_price = $el.value.replace(/\D/g, '')" required aria-invalid="{{ $errors->has('cost_price') ? 'true' : 'false' }}" aria-describedby="cost_price-error" />@error('cost_price')<p class="error-text" id="cost_price-error">{{ $message }}</p>@enderror</div>
        <div><x-ui.field name="entry_date" label="Tanggal masuk" type="date" wire:model="entry_date" required aria-invalid="{{ $errors->has('entry_date') ? 'true' : 'false' }}" aria-describedby="entry_date-error" />@error('entry_date')<p class="error-text" id="entry_date-error">{{ $message }}</p>@enderror</div>
        <div><x-ui.field name="note" label="Catatan (opsional)" wire:model="note" maxlength="1000" aria-invalid="{{ $errors->has('note') ? 'true' : 'false' }}" aria-describedby="note-error" />@error('note')<p class="error-text" id="note-error">{{ $message }}</p>@enderror</div>
        <p class="sub">Restok menambah jumlah tersedia dan mengganti harga beli barang. Restok tidak dicatat sebagai pengeluaran.</p>
        <div class="form-actions"><a class="btn ghost" href="{{ route('stock.index') }}">Kembali ke stok</a><button class="btn primary desktop-only" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan restok</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
        <div class="actionbar"><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan restok</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
    </form>
    <section class="history" aria-labelledby="stock-history-title"><h2 id="stock-history-title">Riwayat restok</h2><p class="sub">Catatan barang masuk yang sudah disimpan.</p>
        <table class="table"><thead><tr><th>Tanggal masuk</th><th class="n">Jumlah</th><th class="n">Harga beli per unit</th><th>Dicatat oleh</th></tr></thead><tbody>
        @forelse($entries as $entry)
            <tr wire:key="entry-{{ $entry->id }}"><td class="history-date">{{ $entry->entry_date->format('d/m/Y') }}@if($entry->note)<span class="history-note">{{ $entry->note }}</span>@endif</td><td class="n history-qty">+{{ number_format($entry->qty, 0, ',', '.') }} unit</td><td class="n history-cost">Rp{{ number_format($entry->cost_price, 0, ',', '.') }}</td><td class="history-by">{{ $entry->creator->name }}</td></tr>
        @empty
            <tr><td colspan="4" class="empty-cell"><div class="empty">Belum ada riwayat restok untuk barang ini.</div></td></tr>
        @endforelse
        </tbody></table>
    </section>
</div>
