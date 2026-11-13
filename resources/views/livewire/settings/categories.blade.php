<div class="category-settings">
    <style>
        .category-settings { overflow-wrap:anywhere; }
        .category-settings .settings-nav { display:flex; flex-wrap:wrap; gap:8px; margin:20px 0 24px; }
        .category-settings .settings-nav a { display:inline-flex; align-items:center; min-height:44px; padding:0 12px; color:var(--muted); }
        .category-settings .settings-nav a[aria-current="page"] { color:var(--text); font-weight:500; }
        .category-settings .form { display:grid; gap:20px; max-width:560px; margin:24px 0 0; }
        .category-settings .field > span { font-weight:500; }
        .category-settings .actions { display:flex; flex-wrap:wrap; gap:8px; }
        .category-settings .notice { margin:0 0 20px; }
        .category-settings .error-text { margin:4px 0 0; }
        @media(max-width:1023.98px) { .category-settings:has(.actionbar) { padding-bottom:84px; } }
    </style>
    <div class="title-row"><div><h1>{{ $mode === 'list' ? 'Kategori' : ($mode === 'create' ? 'Tambah kategori' : 'Ubah kategori') }}</h1><p class="sub">Master kategori barang servis</p></div>
        @if($mode === 'list')<button class="btn primary desktop-only" type="button" wire:click="create">Tambah kategori</button>@endif
    </div>
    <nav class="settings-nav" aria-label="Pengaturan">
        <a href="{{ route('settings.store') }}">Toko</a>
        <a href="{{ route('settings.categories') }}" aria-current="page">Kategori</a>
        <a href="{{ route('users.index') }}">Pengguna</a>
    </nav>
    @if($status)<p class="notice" role="status">{{ $status }}</p>@endif
    @if($errors->any())<p class="notice" role="alert" x-init="$nextTick(() => $root.querySelector('[aria-invalid=true]')?.focus())">Periksa kembali isian di bawah.</p>@endif
    @if($mode === 'list')
        <table class="table"><thead><tr><th class="act">Aksi</th><th>Nama kategori</th><th>Status</th></tr></thead><tbody>
        @forelse($categories as $category)
            <tr wire:key="category-{{ $category->id }}"><td class="act"><x-ui.row-actions :id="'category-actions-'.$category->id" :label="$category->name">
                <button type="button" role="menuitem" onclick="Livewire.find(@js($this->getId())).edit({{ $category->id }})"><x-ui.icon name="settings" />Ubah kategori</button>
                <button class="danger" type="button" role="menuitem" onclick="if (confirm(@js(($category->is_active ? 'Nonaktifkan' : 'Aktifkan').' kategori '.$category->name.'?'))) Livewire.find(@js($this->getId())).toggleActive({{ $category->id }})"><x-ui.icon name="ban" />{{ $category->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
            </x-ui.row-actions></td><td class="c-who">{{ $category->name }}</td><td class="c-meta"><span class="pill">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></td></tr>
        @empty
            <tr><td colspan="3"><div class="empty">Belum ada kategori.</div></td></tr>
        @endforelse
        </tbody></table>
        <div class="actionbar"><button class="btn primary" type="button" wire:click="create">Tambah kategori</button></div>
    @else
        <form class="form" wire:submit="save" novalidate>
            <div><x-ui.field name="name" label="Nama kategori" wire:model="name" maxlength="255" required aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-error" />@error('name')<p class="error-text" id="name-error">{{ $message }}</p>@enderror</div>
            <div class="actions"><button class="btn ghost" type="button" wire:click="cancel">Batal</button><button class="btn primary desktop-only" type="submit" wire:loading.attr="disabled" wire:target="save">Simpan kategori</button></div>
            <div class="actionbar"><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan kategori</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
        </form>
    @endif
</div>
