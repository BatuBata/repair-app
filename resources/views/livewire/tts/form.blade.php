@php
    $categoryOptions = $categories->map(fn ($category) => ['value' => $category->id, 'label' => $category->name]);
    if ($currentCategory && ! $currentCategory->is_active) {
        $categoryOptions->prepend(['value' => $currentCategory->id, 'label' => $currentCategory->name.' (nonaktif)']);
    }
    $categoryNames = $categoryOptions->mapWithKeys(fn ($category) => [(string) $category['value'] => $category['label']]);
@endphp
<div class="tts-compose" x-data="{ categories: @js($categoryNames) }">
    <style>
        .tts-compose { overflow-wrap:anywhere; }
        .tts-compose .compose { display:grid; grid-template-columns:minmax(0,560px) minmax(0,1fr); gap:48px; align-items:start; }
        .tts-compose .preview { position:sticky; top:24px; }
        .tts-compose .preview-bed { padding:24px; background:var(--raised); border-radius:var(--radius-card); display:grid; justify-items:center; gap:12px; }
        .tts-compose .paper { width:260px; padding:16px; border:1px solid var(--border); border-radius:4px; font-size:12px; line-height:17px; background:var(--surface); }
        .tts-compose .paper hr { border:0; border-top:1px dashed var(--control); margin:8px 0; }
        .tts-compose .paper dl { display:grid; grid-template-columns:56px minmax(0,1fr); gap:4px 8px; }
        .tts-compose .paper dt { color:var(--muted); }
        .tts-compose .form-end { display:flex; gap:8px; }
        .tts-compose .error-text { margin-top:4px; }
        @media(max-width:1023.98px) { .tts-compose .compose { display:block; } .tts-compose summary { min-height:44px; height:auto; flex-wrap:wrap; } }
    </style>
    <div class="title-row"><div><h1>{{ $orderId ? 'Ubah TTS' : 'TTS baru' }}</h1><p class="sub desktop-only">Empat isian wajib. {{ $orderId ? 'Perbarui data barang dan customer.' : 'Nomor TTS dibuat otomatis saat disimpan.' }}</p></div></div>
    @if($errors->any())<p class="notice" role="alert" x-init="$nextTick(() => $root.querySelector('[aria-invalid=true]')?.focus())">Periksa kembali isian di bawah.</p>@endif
    <div class="compose">
        <form class="form" id="tts-form" wire:submit="save" novalidate>
            @unless($orderId)
                <div>
                    <x-ui.search-select name="customer_id" label="Pelanggan" :options="$customers->map(fn ($customer) => ['value' => $customer->id, 'label' => $customer->name.' · '.$customer->phone])->prepend(['value' => '', 'label' => 'Pelanggan baru'])" :value="$customer_id" placeholder="Pelanggan baru atau cari pelanggan terdaftar" wire:model.live="customer_id" :invalid="$errors->has('customer_id')" describedby="customer_id-error" />
                    @error('customer_id')<p class="error-text" id="customer_id-error">{{ $message }}</p>@enderror
                    <p class="small muted">Pelanggan baru otomatis terdaftar setelah TTS disimpan.</p>
                </div>
            @endunless
            <fieldset @disabled(!$orderId && $customer_id !== '') style="border:0;padding:0;margin:0;display:grid;gap:20px;min-width:0">
            <div class="field"><label for="customer_name">Nama customer</label><input class="input" id="customer_name" wire:model="customer_name" autocomplete="off" required maxlength="100" placeholder="mis. Bu Ratna Sari" aria-invalid="{{ $errors->has('customer_name') ? 'true' : 'false' }}" aria-describedby="customer_name-error">@error('customer_name')<p class="error-text" id="customer_name-error">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="customer_phone">Nomor HP / WhatsApp</label><input class="input num" id="customer_phone" type="tel" inputmode="tel" wire:model="customer_phone" autocomplete="off" required maxlength="40" placeholder="0812 3456 7890" aria-invalid="{{ $errors->has('customer_phone') ? 'true' : 'false' }}" aria-describedby="customer_phone-error">@error('customer_phone')<p class="error-text" id="customer_phone-error">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="customer_address">Alamat <span class="opt">opsional</span></label><textarea class="textarea" id="customer_address" wire:model="customer_address" maxlength="255" placeholder="mis. Jl. A. Yani Km 4, Gg. Melati 2" aria-invalid="{{ $errors->has('customer_address') ? 'true' : 'false' }}" aria-describedby="customer_address-error"></textarea>@error('customer_address')<p class="error-text" id="customer_address-error">{{ $message }}</p>@enderror</div>
            </fieldset>
            <div>
                <x-ui.search-select name="category_id" label="Barang" :options="$categoryOptions" :value="$category_id" placeholder="Cari dan pilih barang" wire:model="category_id" :invalid="$errors->has('category_id')" describedby="category_id-error" required />
                @if($currentCategory && ! $currentCategory->is_active)<p class="small muted">Kategori sebelumnya: {{ $currentCategory->name }} (nonaktif). Tetap disimpan bila tidak diganti.</p>@endif
                @if($categories->isEmpty())<p class="notice">Belum ada kategori aktif. Hubungi admin toko.</p>@endif
                @error('category_id')<p class="error-text" id="category_id-error">{{ $message }}</p>@enderror
            </div>
            <div class="field"><label for="complaint">Keluhan / kerusakan</label><textarea class="textarea" id="complaint" wire:model="complaint" required maxlength="500" placeholder="mis. Pengering tidak berputar" aria-invalid="{{ $errors->has('complaint') ? 'true' : 'false' }}" aria-describedby="complaint-error"></textarea>@error('complaint')<p class="error-text" id="complaint-error">{{ $message }}</p>@enderror</div>
            <details class="more" @if($errors->has('item_description') || $item_description) open @endif>
                <summary><x-ui.icon name="chevron-down" />Keterangan barang <span class="opt">opsional</span></summary>
                <div class="more-body">
                    <div class="field"><label for="item_description">Keterangan barang</label><input class="input" id="item_description" wire:model="item_description" maxlength="100" placeholder="mis. Sharp 2 tabung, 8 kg" aria-invalid="{{ $errors->has('item_description') ? 'true' : 'false' }}" aria-describedby="item_description-error">@error('item_description')<p class="error-text" id="item_description-error">{{ $message }}</p>@enderror</div>
                </div>
            </details>
            <div class="form-end desktop-only"><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $orderId ? 'Simpan perubahan' : 'Simpan TTS' }}</span><span wire:loading wire:target="save">Menyimpan…</span></button><a class="btn ghost" href="{{ $orderId ? route('tts.show', $orderId) : route('tts.index') }}">Batal</a></div>
            <div class="actionbar"><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $orderId ? 'Simpan perubahan' : 'Simpan TTS' }}</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
        </form>
        <aside class="preview desktop-only" aria-label="Pratinjau tanda terima"><div class="preview-bed"><div class="paper">
            <p style="text-align:center;font-weight:600">{{ $storeName }}</p><hr><p style="text-align:center">TANDA TERIMA SERVIS</p><p class="small muted" style="text-align:center">{{ $orderId ? 'Perubahan data TTS' : 'Nomor terbit saat disimpan' }}</p><hr>
            <dl><dt>Nama</dt><dd x-text="$wire.customer_name || '—'"></dd><dt>HP</dt><dd x-text="$wire.customer_phone || '—'"></dd><dt>Barang</dt><dd x-text="[categories[$wire.category_id], $wire.item_description].filter(Boolean).join(' · ') || '—'"></dd><dt>Keluhan</dt><dd x-text="$wire.complaint || '—'"></dd></dl>
        </div><p class="small muted" style="text-align:center">Periksa data sebelum menyimpan tanda terima.</p></div></aside>
    </div>
</div>
