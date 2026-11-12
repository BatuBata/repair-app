<div class="store-settings">
    <style>
        .store-settings { overflow-wrap: anywhere; }
        .store-settings .settings-nav { display:flex; flex-wrap:wrap; gap:8px; margin:20px 0 24px; }
        .store-settings .settings-nav a { display:inline-flex; align-items:center; min-height:44px; padding:0 12px; color:var(--muted); }
        .store-settings .settings-nav a[aria-current="page"] { color:var(--text); font-weight:500; }
        .store-settings .form { display:grid; gap:20px; max-width:560px; margin:0; }
        .store-settings .field > span { font-weight:500; }
        .store-settings textarea.input { min-height:96px; resize:vertical; }
        .store-settings .logo-preview { display:block; max-width:180px; max-height:120px; object-fit:contain; margin-top:8px; }
        .store-settings .error-text { margin:4px 0 0; }
        .store-settings .actions { display:flex; flex-wrap:wrap; gap:8px; }
        .store-settings .notice { margin:0 0 20px; }
        @media(max-width:1023.98px) { .store-settings:has(.actionbar) { padding-bottom:84px; } }
    </style>
    <div class="title-row"><div><h1>Pengaturan toko</h1><p class="sub">Informasi toko dan garansi</p></div></div>
    <nav class="settings-nav" aria-label="Pengaturan">
        <a href="{{ route('settings.store') }}" aria-current="page">Toko</a>
        <a href="{{ route('settings.categories') }}">Kategori</a>
        <a href="{{ route('users.index') }}">Pengguna</a>
    </nav>
    @if($status)<p class="notice" role="status">{{ $status }}</p>@endif
    @if($errors->any())<p class="notice" role="alert" x-init="$nextTick(() => $root.querySelector('[aria-invalid=true]')?.focus())">Periksa kembali isian di bawah.</p>@endif
    <form class="form" wire:submit="save" novalidate>
        <div>
            <x-ui.field name="name" label="Nama toko" wire:model="name" maxlength="255" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-error" />
            @error('name')<p class="error-text" id="name-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="field"><span>Alamat</span><textarea class="input" name="address" wire:model="address" maxlength="65535" aria-invalid="{{ $errors->has('address') ? 'true' : 'false' }}" aria-describedby="address-error"></textarea></label>
            @error('address')<p class="error-text" id="address-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <x-ui.field name="phone" label="Telepon" type="tel" wire:model="phone" maxlength="15" inputmode="tel" aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}" aria-describedby="phone-error" />
            @error('phone')<p class="error-text" id="phone-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="field"><span>Logo toko</span><input class="input" type="file" accept="image/jpeg,image/png,image/gif,image/webp" wire:model="logo" aria-invalid="{{ $errors->has('logo') ? 'true' : 'false' }}" aria-describedby="logo-error"></label>
            @error('logo')<p class="error-text" id="logo-error">{{ $message }}</p>@enderror
            @if($logo && in_array($logo->getMimeType(), ['image/jpeg', 'image/png', 'image/gif', 'image/webp'], true)) <img class="logo-preview" src="{{ $logo->temporaryUrl() }}" alt="Pratinjau logo yang dipilih">@elseif($logoUrl) <img class="logo-preview" src="{{ $logoUrl }}" alt="Logo toko saat ini">@endif
        </div>
        <div>
            <label class="field"><span>Teks syarat garansi</span><textarea class="input" name="warranty_terms" wire:model="warranty_terms" maxlength="65535" aria-invalid="{{ $errors->has('warranty_terms') ? 'true' : 'false' }}" aria-describedby="warranty_terms-error"></textarea></label>
            @error('warranty_terms')<p class="error-text" id="warranty_terms-error">{{ $message }}</p>@enderror
        </div>
        <div>
            <x-ui.field name="default_warranty_days" label="Default hari garansi" type="number" wire:model="default_warranty_days" min="0" max="4294967295" step="1" inputmode="numeric" aria-invalid="{{ $errors->has('default_warranty_days') ? 'true' : 'false' }}" aria-describedby="default_warranty_days-error" />
            @error('default_warranty_days')<p class="error-text" id="default_warranty_days-error">{{ $message }}</p>@enderror
        </div>
        <div class="actions"><button class="btn primary desktop-only" type="submit" wire:loading.attr="disabled" wire:target="save">Simpan pengaturan toko</button></div>
        <div class="actionbar"><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan pengaturan toko</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
    </form>
</div>
