<div class="sales-page record-detail sale-create">
    @include('livewire.sales.styles')
    @include('livewire.detail-styles')
    <a class="crumb" href="{{ route('sales.index') }}"><x-ui.icon name="arrow-left" />Penjualan</a>
    <header class="title-row"><div><h1>Penjualan baru</h1><p class="sub">Pilih barang, isi pembeli bila perlu, lalu simpan penjualan.</p></div></header>
    @if($errors->any())<x-ui.notice type="error" x-init="$nextTick(() => $root.querySelector('[aria-invalid=true]')?.focus())">Periksa kembali isian di bawah.</x-ui.notice>@endif
    <form class="sales-form sale-create-layout" wire:submit="save" novalidate>
        <div class="sale-create-main">
            <section class="detail-panel" aria-labelledby="sale-customer-title">
                <h2 id="sale-customer-title"><x-ui.icon name="user" />Pelanggan <span class="small muted">opsional</span></h2>
        <div wire:key="sale-customer-{{ $customer_id ?: 'new' }}">
            <x-ui.search-select name="customer_id" label="Cari pelanggan terdaftar" :options="$customers->map(fn ($customer) => ['value' => $customer->id, 'label' => $customer->name.' · '.$customer->phone])" :value="$customer_id" placeholder="Cari nama atau nomor HP pelanggan" wire:model.live="customer_id" :invalid="$errors->has('customer_id')" describedby="customer_id-error" />
            @error('customer_id')<p class="error-text" id="customer_id-error">{{ $message }}</p>@enderror
            @if($customer_id !== '')
                <button class="btn ghost" type="button" wire:click="newCustomer"><x-ui.icon name="plus" />Pelanggan baru / pembeli umum</button>
            @else
                <p class="small muted">Pembeli umum boleh tanpa identitas. Isi nama dan nomor HP untuk mendaftarkan pelanggan baru.</p>
            @endif
        </div>
        <div class="customer-fields">
            @foreach(['customer_name' => ['Nama pelanggan (opsional)', 'text'], 'customer_phone' => ['Nomor HP / WhatsApp (opsional)', 'tel']] as $field => [$label, $type])
                <div><x-ui.field :name="$field" :label="$label" :type="$type" wire:model="{{ $field }}" :disabled="$customer_id !== '' && in_array($field, ['customer_name', 'customer_phone'])" inputmode="{{ $type === 'tel' ? 'tel' : 'text' }}" aria-invalid="{{ $errors->has($field) ? 'true' : 'false' }}" aria-describedby="{{ $field }}-error" />@error($field)<p class="error-text" id="{{ $field }}-error">{{ $message }}</p>@enderror</div>
            @endforeach
        </div>
            </section>
        <section class="detail-panel" aria-labelledby="sale-lines-title"><div class="line-heading"><h2 id="sale-lines-title"><x-ui.icon name="package" />Barang dijual</h2><button class="btn" type="button" wire:click="addItem"><x-ui.icon name="plus" />Tambah barang</button></div>
            @error('items')<p class="error-text">{{ $message }}</p>@enderror
            @foreach($items as $index => $row)
                <div class="sale-line" wire:key="sale-item-{{ $index }}">
                    <div class="remove"><button class="btn" type="button" wire:click="removeItem({{ $index }})" aria-label="Hapus baris barang {{ $index + 1 }}"><x-ui.icon name="trash-2" /></button></div>
                    <div class="product-field"><x-ui.search-select :name="'product-'.$index" label="Barang" :options="$products->map(fn ($product) => ['value' => $product->id, 'label' => $product->name.' · '.number_format($product->stock, 0, ',', '.').' unit'])" :value="$row['product_id']" placeholder="Cari dan pilih barang" wire:change="selectProduct({{ $index }}, $event.target.value)" :invalid="$errors->has('items.'.$index.'.product_id')" :describedby="'product-'.$index.'-error'" required />@if($selectedProduct = $products->firstWhere('id', $row['product_id']))<p class="stock-hint">{{ $selectedProduct->name }} · tersedia {{ number_format($selectedProduct->stock, 0, ',', '.') }} unit</p>@endif@error('items.'.$index.'.product_id')<p class="error-text" id="product-{{ $index }}-error">{{ $message }}</p>@enderror</div>
                    <div class="quantity-field"><x-ui.field :name="'qty-'.$index" label="Jumlah" wire:model.live.debounce.300ms="items.{{ $index }}.qty" inputmode="numeric" required aria-invalid="{{ $errors->has('items.'.$index.'.qty') ? 'true' : 'false' }}" aria-describedby="qty-{{ $index }}-error" />@error('items.'.$index.'.qty')<p class="error-text" id="qty-{{ $index }}-error">{{ $message }}</p>@enderror</div>
                    <div class="price-field"><x-ui.money :name="'price-'.$index" label="Harga jual per unit" :value="$row['price']" x-on:input="$wire.set('items.{{ $index }}.price', $el.value.replace(/\D/g, ''), false)" x-on:change="$wire.$refresh()" required aria-invalid="{{ $errors->has('items.'.$index.'.price') ? 'true' : 'false' }}" aria-describedby="price-{{ $index }}-error" />@error('items.'.$index.'.price')<p class="error-text" id="price-{{ $index }}-error">{{ $message }}</p>@enderror</div>
                </div>
            @endforeach
            @if($products->isEmpty())<x-ui.notice type="warning">Belum ada barang aktif. <a class="btn ghost" href="{{ route('stock.create') }}">Tambah barang stok</a></x-ui.notice>@endif
        </section>
        </div>
        <aside class="detail-panel sale-checkout" aria-labelledby="sale-summary-title">
            <h2 id="sale-summary-title">Ringkasan penjualan</h2>
            <dl class="detail-totals"><div><dt>Subtotal barang</dt><dd>{{ $previewSubtotal === null ? 'Periksa isian' : 'Rp'.number_format($previewSubtotal, 0, ',', '.') }}</dd></div></dl>
            <div class="sale-discount"><x-ui.money name="discount" label="Diskon" :value="$discount" x-on:input="$wire.set('discount', $el.value.replace(/\D/g, ''))" aria-invalid="{{ $errors->has('discount') ? 'true' : 'false' }}" aria-describedby="discount-hint discount-error" /><p class="small muted" id="discount-hint">Isi 0 jika tanpa diskon. Maksimal sebesar subtotal.</p>@error('discount')<p class="error-text" id="discount-error">{{ $message }}</p>@enderror</div>
            <dl class="detail-total"><dt>Total bayar</dt><dd>{{ $previewTotal === null ? 'Periksa isian' : 'Rp'.number_format($previewTotal, 0, ',', '.') }}</dd></dl>
            <div class="sale-transaction-fields">
                <div><x-ui.field name="sold_on" label="Tanggal jual" type="date" :value="$sold_on" wire:model="sold_on" aria-invalid="{{ $errors->has('sold_on') ? 'true' : 'false' }}" aria-describedby="sold_on-error" />@error('sold_on')<p class="error-text" id="sold_on-error">{{ $message }}</p>@enderror</div>
                <div><x-ui.field name="warranty_days" label="Garansi (hari)" wire:model="warranty_days" inputmode="numeric" aria-invalid="{{ $errors->has('warranty_days') ? 'true' : 'false' }}" aria-describedby="warranty_days-hint warranty_days-error" /><p class="small muted" id="warranty_days-hint">0 = tanpa garansi. Garansi memerlukan nomor HP.</p>@error('warranty_days')<p class="error-text" id="warranty_days-error">{{ $message }}</p>@enderror</div>
            </div>
            <section class="sale-payment" aria-labelledby="sale-payment-title"><h2 id="sale-payment-title">Pembayaran</h2><x-payment-fields :method="$payment_method" :bank="$payment_bank" prefix="sale" /></section>
            <p class="small muted sale-save-hint">Stok berkurang saat disimpan. Penjualan tersimpan tidak dapat diubah; untuk koreksi, hapus lalu buat ulang.</p>
        <div class="form-actions sale-save"><button class="btn primary desktop-only" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save"><span class="sale-save-label"><x-ui.icon name="check" />Simpan penjualan</span></span><span wire:loading wire:target="save">Menyimpan…</span></button><a class="btn ghost" href="{{ route('sales.index') }}">Batal</a></div>
        <div class="actionbar"><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save"><span class="sale-save-label"><x-ui.icon name="check" />Simpan penjualan</span></span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
        </aside>
    </form>
</div>
