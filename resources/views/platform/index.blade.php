<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f2f5f6">
    <title>Kelola toko — Repair App</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-repair-shell class="platform-owner">
<div class="app platform-app">
    <nav class="sidebar" aria-label="Menu owner SaaS">
        <a class="brand" href="{{ route('platform.index') }}"><x-ui.mark /><strong>Repair App</strong></a>
        <div class="scope"><x-ui.icon name="shield-check" />Owner SaaS</div>
        <a class="nav-item" href="{{ route('platform.index') }}" aria-current="page"><x-ui.icon name="store" />Toko</a>
        <form class="bottom" method="POST" action="{{ route('platform.logout') }}">@csrf<button class="nav-item" type="submit"><x-ui.icon name="log-out" />Keluar</button></form>
    </nav>
    <div class="main">
        <header class="topbar">
            <a class="phone-brand" href="{{ route('platform.index') }}"><x-ui.mark /><span>Owner SaaS</span></a>
            <form class="phone-only" method="POST" action="{{ route('platform.logout') }}">@csrf<button class="icon-btn" type="submit" aria-label="Keluar"><x-ui.icon name="log-out" /></button></form>
        </header>
        <main class="sheet platform-sheet">
            <div class="title-row">
                <div><h1>Kelola toko</h1><p class="sub"><strong>{{ $activeTenants }} aktif</strong> dari {{ $totalTenants }} toko.</p></div>
            </div>

            @if(session('status'))<x-ui.notice type="success">{{ session('status') }}</x-ui.notice>@endif
            @error('tenant')<x-ui.notice type="error">{{ $message }}</x-ui.notice>@enderror

            <div class="grid platform-grid">
                <section class="span-8" aria-labelledby="tenant-list-title">
                    <div class="section-head"><h2 id="tenant-list-title">Client</h2><span class="meta num">{{ $tenants->total() }} toko</span></div>
                    <form method="GET" action="{{ route('platform.index') }}" class="index-search-form">
                        <x-ui.index-search label="Cari toko" placeholder="Cari toko, owner, atau username" :total="$tenants->total()" unit="toko" name="search" :value="$search" />
                        <button class="btn sm" type="submit">Cari</button>
                    </form>
                    @if($tenants->isEmpty())
                        <div class="empty"><strong>{{ $search !== '' ? 'Tidak ada toko yang cocok.' : 'Belum ada toko.' }}</strong><span>Tambahkan client pertama melalui form.</span></div>
                    @else
                        <table class="table platform-table">
                            <thead><tr><th>Aksi</th><th>Toko</th><th>Owner</th><th>Dibuat</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach($tenants as $tenant)
                                @php($owner = $owners->get($tenant->id))
                                <tr>
                                    <td class="act"><form method="POST" action="{{ route('platform.tenants.toggle', $tenant) }}">@csrf @method('PATCH')<button class="btn sm {{ $tenant->is_active ? 'danger' : '' }}" type="submit">{{ $tenant->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form></td>
                                    <td class="c-id"><strong>{{ $tenant->name }}</strong></td>
                                    <td class="c-who">{{ $owner?->name ?? '—' }}<span class="sub">{{ $owner?->username ?? 'Owner belum tersedia' }}</span></td>
                                    <td class="c-what num">{{ $tenant->created_at?->format('d M Y') ?? '—' }}</td>
                                    <td class="c-st"><span class="pill {{ $tenant->is_active ? 'st-selesai' : 'st-batal' }}">{{ $tenant->is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                    {{ $tenants->onEachSide(1)->links('components.ui.pagination') }}
                </section>

                <section class="panel span-4 platform-create" aria-labelledby="create-tenant-title">
                    <header><div><h2 id="create-tenant-title">Tambah toko</h2><p class="meta">Buat toko dan akun owner pertama.</p></div></header>
                    <form class="form" method="POST" action="{{ route('platform.tenants.store') }}" novalidate>
                        @csrf
                        <div class="field"><label for="store_name">Nama toko</label><input class="input" id="store_name" name="store_name" maxlength="255" required value="{{ old('store_name') }}" placeholder="mis. Sumber HP" @if($errors->has('store_name')) aria-invalid="true" aria-describedby="store-name-error" @endif>@error('store_name')<p class="error-text" id="store-name-error">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="name">Nama owner</label><input class="input" id="name" name="name" maxlength="255" required value="{{ old('name') }}" placeholder="mis. Budi Santoso" @if($errors->has('name')) aria-invalid="true" aria-describedby="name-error" @endif>@error('name')<p class="error-text" id="name-error">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="username">Username owner</label><input class="input" id="username" name="username" maxlength="100" autocomplete="off" autocapitalize="none" spellcheck="false" required value="{{ old('username') }}" placeholder="mis. budi" @if($errors->has('username')) aria-invalid="true" aria-describedby="username-error" @endif>@error('username')<p class="error-text" id="username-error">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="password">Password awal</label><input class="input" id="password" name="password" type="password" minlength="9" autocomplete="new-password" required placeholder="Minimal 9 karakter" @if($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>@error('password')<p class="error-text" id="password-error">{{ $message }}</p>@enderror</div>
                        <div class="field"><label for="password_confirmation">Konfirmasi password</label><input class="input" id="password_confirmation" name="password_confirmation" type="password" minlength="9" autocomplete="new-password" required placeholder="Ulangi password awal"></div>
                        <button class="btn primary" type="submit"><x-ui.icon name="plus" />Buat toko</button>
                    </form>
                </section>
            </div>
        </main>
    </div>
</div>
</body>
</html>
