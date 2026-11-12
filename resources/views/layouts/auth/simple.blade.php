@props(['title' => 'Masuk'])
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f2f5f6">
    <title>{{ $title }} — Anugerah</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body.public { min-height:100svh; display:grid; place-items:center; padding:24px 16px; background:var(--ground); }
        .login { width:100%; max-width:380px; display:grid; gap:24px; }
        .login .brand { padding:0; height:auto; gap:10px; }
        .login .brand-mark { width:28px; height:28px; }
        .login .brand strong { font-size:18px; }
        .login .card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-sheet); padding:28px; display:grid; gap:20px; }
        .login h1 { font-size:20px; line-height:28px; font-weight:600; }
        .login .pw { position:relative; }
        .login .pw .input { padding-right:52px; }
        .login .pw .icon-btn { position:absolute; right:0; top:50%; translate:0 -50%; width:44px; height:44px; }
        .login .btn.primary { height:44px; }
        .login .check { align-items:center; min-height:44px; margin-block:-12px; }
        .login .help { text-align:center; }
        @media(max-width:1023.98px) { .login .card { padding:24px 20px; } }
    </style>
</head>
<body data-repair-shell class="no-shell public">
<main class="login">
    <div class="brand"><x-ui.mark /><strong>Anugerah</strong></div>
    {{ $slot }}
    <p class="small muted help">Lupa password? Minta admin toko untuk mengatur ulang.</p>
</main>
@fluxScripts
</body>
</html>
