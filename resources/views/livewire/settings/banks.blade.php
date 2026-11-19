<div class="bank-settings">
    <style>
        .bank-settings { overflow-wrap:anywhere; }
        .bank-settings .settings-nav { display:flex; flex-wrap:wrap; gap:8px; margin:20px 0 24px; }
        .bank-settings .settings-nav a { display:inline-flex; align-items:center; min-height:44px; padding:0 12px; color:var(--muted); }
        .bank-settings .settings-nav a[aria-current="page"] { color:var(--text); font-weight:500; }
    </style>
    <div class="title-row"><div><h1>{{ $editing ? ($bankId ? 'Edit bank' : 'Tambah bank') : 'Bank' }}</h1><p class="sub">Atur bank pembayaran untuk toko ini.</p></div>
        @unless($editing)<button class="btn primary" type="button" wire:click="create">Tambah bank</button>@endunless
    </div>
    <nav class="settings-nav" aria-label="Pengaturan"><a href="{{ route('settings.store') }}">Toko</a><a href="{{ route('settings.categories') }}">Kategori</a><a href="{{ route('settings.banks') }}" aria-current="page">Bank</a><a href="{{ route('settings.expense-categories') }}">Kategori pengeluaran</a><a href="{{ route('users.index') }}">Pengguna</a></nav>
    @if(session('bank_status'))<x-ui.notice type="success">{{ session('bank_status') }}</x-ui.notice>@endif
    @if($editing)
        <form class="form" wire:submit="save" style="max-width:560px" novalidate>
            <div class="field"><label for="bank-name">Nama bank</label><input class="input" id="bank-name" wire:model="name" maxlength="50" required placeholder="mis. Bank Jago" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="bank-name-error">@error('name')<p id="bank-name-error" class="error-text">{{ $message }}</p>@enderror</div>
            <div><button type="button" class="btn ghost" wire:click="cancel">Batal</button><button class="btn primary" type="submit" wire:loading.attr="disabled">Simpan bank</button></div>
        </form>
    @else
        <p class="small muted" style="margin:16px 0">Bank bawaan hanya bisa diaktifkan/nonaktifkan. Bank tambahan bisa diedit dan dihapus. Pengaturan toko lain serta pembayaran lama tetap sama.</p>
        <x-ui.index-search label="Cari bank" placeholder="Cari nama bank" :total="$banks->total()" unit="bank" wire:model.live.debounce.300ms="search" />
    <table class="table"><thead><tr><th class="act">Aksi</th><th>Nama bank</th><th>Jenis</th><th>Status</th></tr></thead><tbody>
            @foreach($banks as $bank)
                <tr wire:key="bank-{{ $bank['name'] }}"><td class="act"><x-ui.row-actions :id="'bank-actions-'.($loop->index)" :label="$bank['name']">
                    @if($bank['is_default'])
                        <button type="button" role="menuitem" onclick="Livewire.find(@js($this->getId())).toggleDefault(@js($bank['name']))"><x-ui.icon name="ban" />{{ $bank['is_active'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    @else
                        <button type="button" role="menuitem" onclick="Livewire.find(@js($this->getId())).edit({{ $bank['id'] }})"><x-ui.icon name="pencil" />Edit bank</button>
                        <button type="button" role="menuitem" onclick="Livewire.find(@js($this->getId())).toggle({{ $bank['id'] }})"><x-ui.icon name="ban" />{{ $bank['is_active'] ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                        <button type="button" role="menuitem" class="danger" onclick="if (confirm(@js('Hapus bank '.$bank['name'].'? Pembayaran lama tetap tersimpan.'))) Livewire.find(@js($this->getId())).delete({{ $bank['id'] }})"><x-ui.icon name="trash-2" />Hapus bank</button>
                    @endif
                </x-ui.row-actions></td><td class="c-who">{{ $bank['name'] }}</td><td class="c-meta">{{ $bank['is_default'] ? 'Bawaan' : 'Tambahan toko' }}</td><td class="c-st"><span class="pill">{{ $bank['is_active'] ? 'Aktif' : 'Nonaktif' }}</span></td></tr>
            @endforeach
        </tbody></table>
    {{ $banks->onEachSide(1)->links('components.ui.pagination', ['livewire' => true]) }}
    @endif
</div>
