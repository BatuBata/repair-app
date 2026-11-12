<div class="account-screen" x-data>
    <style>.account-screen { overflow-wrap:anywhere; } .account-screen .actions { display:flex; flex-wrap:wrap; gap:8px; } .account-screen .form { margin-top:24px; } .account-screen .error-text { margin-top:4px; } .account-screen .notice { margin-bottom:20px; } .account-screen .field > span { font-weight:500; } .account-screen .check { align-items:center; min-height:44px; } .account-screen select[aria-invalid="true"] { border-color:var(--negative); } @media(max-width:1023.98px) { .account-screen:has(.actionbar) { padding-bottom:84px; } }</style>
    <div class="title-row"><div><h1>{{ $mode === 'list' ? 'Pengguna' : ($mode === 'create' ? 'Tambah pengguna' : ($mode === 'reset' ? 'Reset password' : 'Ubah pengguna')) }}</h1><p class="sub">{{ $mode === 'reset' ? $name : 'Akun Toko Anugerah' }}</p></div>
        @if($mode === 'list')<button class="btn primary desktop-only" type="button" wire:click="create">Tambah pengguna</button>@endif
    </div>
    @if($status)<p class="notice" role="status">{{ $status }}</p>@endif
    @if($errors->any())
        <div class="notice" role="alert" x-init="$nextTick(() => $root.querySelector('[aria-invalid=true]')?.focus())">Periksa kembali isian di bawah.</div>
    @endif
    @if($mode === 'list')
        @error('is_active')<p class="error-text" role="alert">{{ $message }}</p>@enderror
        <table class="table"><thead><tr><th class="act">Aksi</th><th>Username</th><th>Nama</th><th>Peran</th><th>Status</th></tr></thead><tbody>
        @forelse($users as $account)
            <tr wire:key="user-{{ $account->id }}"><td class="act">{{-- Menu shared berpindah ke body; panggil komponen berdasarkan ID agar aksi tetap terhubung. --}}<x-ui.row-actions :id="'user-actions-'.$account->id" :label="$account->name">
                <button type="button" role="menuitem" onclick="Livewire.find(@js($this->getId())).edit({{ $account->id }})"><x-ui.icon name="settings" />Ubah pengguna</button>
                <button type="button" role="menuitem" onclick="Livewire.find(@js($this->getId())).resetPassword({{ $account->id }})"><x-ui.icon name="lock" />Reset password</button>
                <hr role="separator"><button class="danger" type="button" role="menuitem" onclick="if (confirm(@js(($account->is_active ? 'Nonaktifkan' : 'Aktifkan').' akun '.$account->name.'?'))) Livewire.find(@js($this->getId())).toggleActive({{ $account->id }})"><x-ui.icon name="user-round" />{{ $account->is_active ? 'Nonaktifkan akun' : 'Aktifkan akun' }}</button>
            </x-ui.row-actions></td><td class="c-id">{{ $account->username }}</td><td class="c-who">{{ $account->name }}</td><td class="c-meta">{{ $account->role === \App\Enums\UserRole::Admin ? 'Admin' : 'Teknisi' }}</td><td class="c-st"><span class="pill">{{ $account->is_active ? 'Aktif' : 'Nonaktif' }}</span></td></tr>
        @empty
            <tr><td colspan="5"><div class="empty">Belum ada pengguna.</div></td></tr>
        @endforelse
        </tbody></table>
        <div class="actionbar"><button class="btn primary" type="button" wire:click="create">Tambah pengguna</button></div>
    @else
        <form class="form" wire:submit="save" novalidate>
        @unless($mode === 'reset')
<div><x-ui.field name="name" label="Nama" type="text" wire:model="name" autocomplete="name" maxlength="255" required aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-error" />@error('name')<p class="error-text" id="name-error">{{ $message }}</p>@enderror</div>
<div><x-ui.field name="username" label="Username" type="text" wire:model="username" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="100" required aria-invalid="{{ $errors->has('username') ? 'true' : 'false' }}" aria-describedby="username-error" />@error('username')<p class="error-text" id="username-error">{{ $message }}</p>@enderror</div>
            <div class="field"><label for="role">Peran</label><select class="select" id="role" wire:model="role" aria-invalid="{{ $errors->has('role') ? 'true' : 'false' }}" aria-describedby="role-error"><option value="teknisi">Teknisi</option><option value="admin">Admin</option></select>@error('role')<p class="error-text" id="role-error">{{ $message }}</p>@enderror</div>
            <div><label class="check"><input type="checkbox" wire:model="is_active" aria-invalid="{{ $errors->has('is_active') ? 'true' : 'false' }}" aria-describedby="is_active-error"><span>Akun aktif</span></label>@error('is_active')<p class="error-text" id="is_active-error">{{ $message }}</p>@enderror</div>
        @endunless
        @if($mode !== 'edit')
<div><x-ui.field name="password" label="Password baru" type="password" wire:model="password" autocomplete="new-password" required aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-error" />@error('password')<p class="error-text" id="password-error">{{ $message }}</p>@enderror</div>
<div><x-ui.field name="password_confirmation" label="Konfirmasi password baru" type="password" wire:model="password_confirmation" autocomplete="new-password" required aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" aria-describedby="password_confirmation-error" />@error('password_confirmation')<p class="error-text" id="password_confirmation-error">{{ $message }}</p>@enderror</div>
            <p class="hint">Gunakan minimal {{ app()->isProduction() ? '12 karakter, huruf besar, huruf kecil, angka, dan simbol' : '8 karakter' }}.</p>
        @endif
            <div class="actions"><button class="btn ghost" type="button" wire:click="cancel">Batal</button><button class="btn primary desktop-only" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan pengguna</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
            <div class="actionbar"><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">Simpan pengguna</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
        </form>
    @endif
</div>
