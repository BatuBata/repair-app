@php
    $user = auth()->user();
    $role = strtolower((string) ($user->role->value ?? $user->role ?? 'admin'));
    $technician = $role === 'teknisi';
    $items = $technician
        ? [['Buat TTS', '/tts/baru', 'plus'], ['Daftar TTS', '/tts', 'clipboard-list'], ['Profil', '/profil', 'user-round']]
        : [['Beranda', '/', 'layout-dashboard'], ['Servis', '/tts', 'clipboard-list'], ['Penjualan', '/penjualan', 'shopping-bag'], ['Stok barang', '/stok', 'boxes'], ['Pengeluaran', '/pengeluaran', 'wallet'], ['Laporan', '/laporan', 'chart-column']];
    $bottom = $technician ? $items : [['Beranda', '/', 'layout-dashboard'], ['Servis', '/tts', 'clipboard-list'], ['Buat TTS', '/tts/baru', 'plus'], ['Laporan', '/laporan', 'chart-column']];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ filled($title ?? null) ? $title.' — Anugerah' : 'Anugerah' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
    <meta name="theme-color" content="#f2f5f6">
    <link rel="manifest" href="/manifest.webmanifest">
</head>
<body data-repair-shell>
<div class="app">
    <nav class="sidebar" aria-label="Menu utama">
        <a class="brand" href="/"><x-ui.mark /><strong>Anugerah</strong></a>
        <div class="scope"><x-ui.icon name="store" />Toko Anugerah</div>
        @foreach ($items as [$label, $href, $icon])
            <a class="nav-item" href="{{ $href }}" @if(request()->path() === ltrim($href, '/') || ($href === '/' && request()->is('/'))) aria-current="page" @endif><x-ui.icon :name="$icon" />{{ $label }}</a>
        @endforeach
        <div class="bottom">
            @unless($technician)<a class="nav-item" href="/pengaturan/toko"><x-ui.icon name="settings" />Pengaturan</a>@endunless
            @auth
                <a class="nav-item" href="https://github.com/laravel/livewire-starter-kit" target="_blank" rel="noopener">Repository</a>
                <a class="nav-item" href="https://laravel.com/docs/starter-kits#livewire" target="_blank" rel="noopener">Documentation</a>
                <a class="nav-item" href="{{ route('profile.edit') }}"><x-ui.icon name="user-round" />{{ $user->name }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="nav-item" type="submit" data-test="logout-button">Keluar</button></form>
            @endauth
        </div>
    </nav>
    <div class="main">
        <header class="topbar">
            <a class="phone-brand" href="/"><x-ui.mark /><span>Anugerah</span></a>
            <form class="search" action="/tts"><label><span class="sr-only">Cari nomor TTS, nama, atau HP</span><x-ui.icon name="search" /><input name="q" type="search" placeholder="Cari nomor TTS, nama, atau HP"><kbd>/</kbd></label></form>
            @auth<a class="icon-btn phone-only" href="{{ route('profile.edit') }}" aria-label="Akun"><x-ui.icon name="user-round" /></a>@endauth
        </header>
        <main class="sheet">{{ $slot }}</main>
    </div>
</div>
<nav class="bottomnav" aria-label="Menu utama">
    @foreach ($bottom as [$label, $href, $icon])
        <a href="{{ $href }}" @class(['create' => !$technician && $label === 'Buat TTS'])>@if(!$technician && $label === 'Buat TTS')<span class="bubble"><x-ui.icon :name="$icon" /></span>@else<x-ui.icon :name="$icon" />@endif{{ $label }}</a>
    @endforeach
    @unless($technician)<button type="button" class="bottom-menu" data-open="navigation-menu"><x-ui.icon name="menu" />Menu</button>@endunless
</nav>
@unless($technician)
<dialog class="sheet-dialog" id="navigation-menu" aria-labelledby="navigation-title"><div class="dialog-body"><h2 id="navigation-title">Menu</h2><nav class="menu-links"><a href="/penjualan">Penjualan</a><a href="/stok">Stok</a><a href="/pengeluaran">Pengeluaran</a><a href="/pengaturan/toko">Pengaturan</a><a href="/profil">Profil</a>@auth<a href="https://github.com/laravel/livewire-starter-kit" target="_blank" rel="noopener">Repository</a><a href="https://laravel.com/docs/starter-kits#livewire" target="_blank" rel="noopener">Documentation</a><a href="{{ route('profile.edit') }}">Pengaturan akun</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Keluar</button></form>@endauth</nav><button class="btn" type="button" data-close>Tutup</button></div></dialog>
@endunless
<div class="toast" role="status" aria-live="polite"></div>
@fluxScripts
</body>
</html>
