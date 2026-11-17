<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f2f5f6">
    <title>Owner SaaS — Aplikasi Servis</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-repair-shell class="no-shell public platform-login">
<main class="login">
    <div class="brand"><x-ui.mark /><strong>Aplikasi Servis</strong></div>
    <form class="card" method="POST" action="{{ route('platform.login.store') }}" novalidate>
        @csrf
        <header><h1>Masuk owner SaaS</h1><p class="muted">Kelola toko yang memakai aplikasi.</p></header>
        <div class="field">
            <label for="username">Username</label>
            <input class="input" id="username" name="username" autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="100" required autofocus value="{{ old('username') }}" placeholder="Masukkan username" @if($errors->has('username')) aria-invalid="true" aria-describedby="username-error" @endif>
            @error('username')<p class="error-text" id="username-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input class="input" id="password" name="password" type="password" autocomplete="current-password" maxlength="255" required placeholder="Masukkan password" @if($errors->has('password')) aria-invalid="true" aria-describedby="password-error" @endif>
            @error('password')<p class="error-text" id="password-error" role="alert">{{ $message }}</p>@enderror
        </div>
        <button class="btn primary block" type="submit">Masuk</button>
    </form>
    <p class="small muted help">Akun toko masuk melalui halaman login biasa.</p>
</main>
</body>
</html>
