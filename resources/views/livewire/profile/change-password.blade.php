<div class="account-screen" x-data>
    <style>.account-screen { overflow-wrap:anywhere; } .account-screen .actions { display:flex; flex-wrap:wrap; gap:8px; } .account-screen .form { margin-top:24px; } .account-screen .error-text { margin-top:4px; } .account-screen .notice { margin-bottom:20px; } .account-screen .field > span { font-weight:500; } .account-screen .check { align-items:center; min-height:44px; } .account-screen select[aria-invalid="true"] { border-color:var(--negative); } @media(max-width:1023.98px) { .account-screen:has(.actionbar) { padding-bottom:84px; } }</style>
    <div class="title-row"><div><h1>Profil</h1><p class="sub">{{ $user->name }} · {{ $user->username }}</p></div></div>
    @if($status)<p class="notice" role="status">{{ $status }}</p>@endif
    @if($errors->any())
        <div class="notice" role="alert" x-init="$nextTick(() => $root.querySelector('[aria-invalid=true]')?.focus())">Periksa kembali isian di bawah.</div>
    @endif
    <h2 style="font-size:var(--fs-section);font-weight:600">Ganti password</h2>
    <form class="form" wire:submit="updatePassword" novalidate>
<div><x-ui.field name="current_password" label="Password saat ini" type="password" wire:model="current_password" autocomplete="current-password" required aria-invalid="{{ $errors->has('current_password') ? 'true' : 'false' }}" aria-describedby="current_password-error" />@error('current_password')<p class="error-text" id="current_password-error">{{ $message }}</p>@enderror</div>
<div><x-ui.field name="password" label="Password baru" type="password" wire:model="password" autocomplete="new-password" required aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" aria-describedby="password-error" />@error('password')<p class="error-text" id="password-error">{{ $message }}</p>@enderror</div>
<div><x-ui.field name="password_confirmation" label="Konfirmasi password baru" type="password" wire:model="password_confirmation" autocomplete="new-password" required aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" aria-describedby="password_confirmation-error" />@error('password_confirmation')<p class="error-text" id="password_confirmation-error">{{ $message }}</p>@enderror</div>
        <p class="hint">Gunakan minimal {{ app()->isProduction() ? '12 karakter, huruf besar, huruf kecil, angka, dan simbol' : '8 karakter' }}.</p>
        <button class="btn primary desktop-only" type="submit" wire:loading.attr="disabled" wire:target="updatePassword"><span wire:loading.remove wire:target="updatePassword">Simpan password</span><span wire:loading wire:target="updatePassword">Menyimpan…</span></button>
        <div class="actionbar"><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="updatePassword"><span wire:loading.remove wire:target="updatePassword">Simpan password</span><span wire:loading wire:target="updatePassword">Menyimpan…</span></button></div>
    </form>
    <form method="POST" action="{{ route('logout') }}" style="margin-top:24px">@csrf<button class="btn" type="submit">Keluar</button></form>
</div>
