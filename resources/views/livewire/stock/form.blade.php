<div class="stock-page">
    @include('livewire.stock.styles')
    <div class="title-row"><div><h1>{{ $productId ? 'Ubah barang' : 'Tambah barang' }}</h1><p class="sub">{{ $productId ? 'Perbarui nama dan harga barang jualan' : 'Catat harga dan stok awal barang jualan' }}</p></div></div>
    @if($errors->any())<p class="notice" role="alert" x-init="$nextTick(() => $root.querySelector('[aria-invalid=true]')?.focus())">Periksa kembali isian di bawah.</p>@endif
    <form class="stock-form" wire:submit="save" novalidate>
        <div><x-ui.field name="name" label="Nama barang" wire:model="name" maxlength="255" required aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-error" />@error('name')<p class="error-text" id="name-error">{{ $message }}</p>@enderror</div>
        @foreach(['cost_price' => 'Harga beli', 'sell_price' => 'Harga jual'] as $field => $label)
            <div><x-ui.money :name="$field" :label="$label" :value="$$field" x-on:input="$wire.{{ $field }} = $el.value.replace(/\D/g, '')" required aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}" aria-describedby="{{ $field }}-error" />@error($field)<p class="error-text" id="{{ $field }}-error">{{ $message }}</p>@enderror</div>
        @endforeach
        @if(! $productId)
            <div><x-ui.field name="stock" label="Stok awal (unit)" wire:model="stock" inputmode="numeric" required aria-invalid="{{ $errors->has('stock') ? 'true' : 'false' }}" aria-describedby="stock-error" />@error('stock')<p class="error-text" id="stock-error">{{ $message }}</p>@enderror</div>
        @else
            <p class="notice">Penambahan stok dicatat melalui <a href="{{ route('stock.restock', $productId) }}" class="btn ghost">Restok &amp; riwayat</a>.</p>
        @endif
        <div class="form-actions"><a class="btn ghost" href="{{ route('stock.index') }}">Batal</a><button class="btn primary desktop-only" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan barang</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
        <div class="actionbar"><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan barang</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
    </form>
</div>
