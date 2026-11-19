<div class="expense-category-settings">
    <style>
        .expense-category-settings { overflow-wrap:anywhere; }
        .expense-category-settings .settings-nav { display:flex; flex-wrap:wrap; gap:8px; margin:20px 0 24px; }
        .expense-category-settings .settings-nav a { display:inline-flex; align-items:center; min-height:44px; padding:0 12px; color:var(--muted); }
        .expense-category-settings .settings-nav a[aria-current="page"] { color:var(--text); font-weight:500; }
    </style>
    <div class="title-row"><div><h1>{{ $editing ? ($categoryId ? 'Edit kategori' : 'Tambah kategori') : 'Kategori pengeluaran' }}</h1><p class="sub">Atur kategori pengeluaran untuk toko ini.</p></div>
        @unless($editing)<button class="btn primary" type="button" wire:click="create">Tambah kategori</button>@endunless
    </div>
    <nav class="settings-nav" aria-label="Pengaturan"><a href="{{ route('settings.store') }}">Toko</a><a href="{{ route('settings.categories') }}">Kategori</a><a href="{{ route('settings.banks') }}">Bank</a><a href="{{ route('settings.expense-categories') }}" aria-current="page">Kategori pengeluaran</a><a href="{{ route('users.index') }}">Pengguna</a></nav>
    @if(session('expense_category_status'))<x-ui.notice type="success">{{ session('expense_category_status') }}</x-ui.notice>@endif
    @if($editing)
        <form class="form" wire:submit="save" style="max-width:560px" novalidate>
            <div class="field"><label for="expense-category-name">Nama kategori</label><input class="input" id="expense-category-name" wire:model="name" maxlength="50" required placeholder="mis. Listrik" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="expense-category-name-error">@error('name')<p id="expense-category-name-error" class="error-text">{{ $message }}</p>@enderror</div>
            <div><button type="button" class="btn ghost" wire:click="cancel">Batal</button><button class="btn primary" type="submit" wire:loading.attr="disabled">Simpan kategori</button></div>
        </form>
    @else
        <p class="small muted" style="margin:16px 0">Kategori bawaan hanya bisa diaktifkan/nonaktifkan. Kategori tambahan bisa diedit dan dihapus. Pengaturan toko lain serta pengeluaran lama tetap sama.</p>
        <x-ui.index-search label="Cari kategori" placeholder="Cari nama kategori" :total="$categories->total()" unit="kategori" wire:model.live.debounce.300ms="search" />
        <table class="table"><thead><tr><th class="act">Aksi</th><th>Nama kategori</th><th>Jenis</th><th>Status</th></tr></thead><tbody>
            @forelse($categories as $category)
                <tr wire:key="expense-category-{{ $category['name'] }}"><td class="act"><x-ui.row-actions :id="'expense-category-actions-'.($loop->index)" :label="$category['name']">
                    @if($category['is_default'])
                        <button type="button" role="menuitem" onclick="Livewire.find(@js($this->getId())).toggleDefault(@js($category['name']))"><x-ui.icon name="ban" />{{ $category['is_active'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    @else
                        <button type="button" role="menuitem" onclick="Livewire.find(@js($this->getId())).edit({{ $category['id'] }})"><x-ui.icon name="pencil" />Edit kategori</button>
                        <button type="button" role="menuitem" onclick="Livewire.find(@js($this->getId())).toggle({{ $category['id'] }})"><x-ui.icon name="ban" />{{ $category['is_active'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        <button type="button" role="menuitem" class="danger" onclick="if (confirm(@js('Hapus kategori '.$category['name'].'? Pengeluaran lama tetap tersimpan.'))) Livewire.find(@js($this->getId())).delete({{ $category['id'] }})"><x-ui.icon name="trash-2" />Hapus kategori</button>
                    @endif
                </x-ui.row-actions></td><td class="c-who">{{ $category['name'] }}</td><td class="c-meta">{{ $category['is_default'] ? 'Bawaan' : 'Tambahan toko' }}</td><td class="c-st"><span class="pill">{{ $category['is_active'] ? 'Aktif' : 'Nonaktif' }}</span></td></tr>
            @empty
                <tr><td colspan="4"><div class="empty">Kategori tidak ditemukan.</div></td></tr>
            @endforelse
        </tbody></table>
    {{ $categories->onEachSide(1)->links('components.ui.pagination', ['livewire' => true]) }}
    @endif
</div>
