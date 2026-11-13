<div class="sales-page">
    @include('livewire.sales.styles')
    <div class="title-row"><div><h1>Penjualan baru</h1><p class="sub">Stok berkurang setelah penjualan disimpan</p></div></div>
    @if($errors->any())<p class="notice" role="alert" x-init="$nextTick(() => $root.querySelector('[aria-invalid=true]')?.focus())">Periksa kembali isian di bawah.</p>@endif
    <form class="sales-form" wire:submit="save" novalidate>
        <div class="customer-fields">
            @foreach(['sold_on' => ['Tanggal jual', 'date'], 'customer_name' => ['Nama customer (opsional)', 'text'], 'customer_phone' => ['Nomor HP / WhatsApp (opsional)', 'tel'], 'warranty_days' => ['Garansi (hari, 0 = tanpa garansi)', 'text']] as $field => [$label, $type])
                <div><x-ui.field :name="$field" :label="$label" :type="$type" wire:model="{{ $field }}" inputmode="{{ $field === 'warranty_days' ? 'numeric' : ($field === 'customer_phone' ? 'tel' : 'text') }}" aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}" aria-describedby="{{ $field }}-error" />@error($field)<p class="error-text" id="{{ $field }}-error">{{ $message }}</p>@enderror</div>
            @endforeach
        </div>
        <section aria-labelledby="sale-lines-title"><div class="line-heading"><h2 id="sale-lines-title">Barang dijual</h2><button class="btn" type="button" wire:click="addItem">Tambah barang</button></div>
            @error('items')<p class="error-text">{{ $message }}</p>@enderror
            @foreach($items as $index => $row)
                <div class="sale-line" wire:key="sale-item-{{ $index }}">
                    <div class="remove"><button class="btn" type="button" wire:click="removeItem({{ $index }})" aria-label="Hapus baris barang {{ $index + 1 }}"><x-ui.icon name="trash-2" /></button></div>
                    <div class="product-field"><label class="field"><span>Barang</span><select class="select" wire:change="selectProduct({{ $index }}, $event.target.value)" aria-label="Barang baris {{ $index + 1 }}" aria-invalid="{{ $errors->has('items.'.$index.'.product_id') ? 'true' : 'false' }}" aria-describedby="product-{{ $index }}-error"><option value="" disabled @selected($row['product_id'] === '')>Pilih barang</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string) $product->id === (string) $row['product_id'])>{{ $product->name }} · {{ number_format($product->stock, 0, ',', '.') }} unit</option>@endforeach</select></label>@if($selectedProduct = $products->firstWhere('id', $row['product_id']))<p class="stock-hint">{{ $selectedProduct->name }} · tersedia {{ number_format($selectedProduct->stock, 0, ',', '.') }} unit</p>@endif@error('items.'.$index.'.product_id')<p class="error-text" id="product-{{ $index }}-error">{{ $message }}</p>@enderror</div>
                    <div class="quantity-field"><x-ui.field :name="'qty-'.$index" label="Jumlah" wire:model.live.debounce.300ms="items.{{ $index }}.qty" inputmode="numeric" required aria-invalid="{{ $errors->has('items.'.$index.'.qty') ? 'true' : 'false' }}" aria-describedby="qty-{{ $index }}-error" />@error('items.'.$index.'.qty')<p class="error-text" id="qty-{{ $index }}-error">{{ $message }}</p>@enderror</div>
                    <div class="price-field"><x-ui.money :name="'price-'.$index" label="Harga jual per unit" :value="$row['price']" x-on:input="$wire.set('items.{{ $index }}.price', $el.value.replace(/\D/g, ''), false)" x-on:change="$wire.$refresh()" required aria-invalid="{{ $errors->has('items.'.$index.'.price') ? 'true' : 'false' }}" aria-describedby="price-{{ $index }}-error" />@error('items.'.$index.'.price')<p class="error-text" id="price-{{ $index }}-error">{{ $message }}</p>@enderror</div>
                </div>
            @endforeach
            @if($products->isEmpty())<p class="notice">Belum ada barang aktif. <a class="btn ghost" href="{{ route('stock.create') }}">Tambah barang stok</a></p>@endif
        </section>
        <div class="sale-summary"><div class="summary-line total"><span>Total sementara</span><strong>{{ $previewTotal === null ? 'Periksa isian' : 'Rp'.number_format($previewTotal, 0, ',', '.') }}</strong></div><p class="sub">Garansi memerlukan nomor HP. Penjualan yang sudah disimpan tidak dapat diubah; hapus lalu buat ulang bila ada koreksi.</p></div>
        <div class="form-actions"><a class="btn ghost" href="{{ route('sales.index') }}">Batal</a><button class="btn primary desktop-only" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan penjualan</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
        <div class="actionbar"><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan penjualan</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
    </form>
</div>
