<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f2f5f6">
    <meta name="robots" content="noindex">
    <title>Status servis {{ $order->number }} — {{ $store?->name ?: 'Anugerah' }}</title>
    @vite(['resources/css/app.css', 'resources/css/layout.css'])
    <style>
        .pub-head { max-width:640px; margin:0 auto; display:flex; align-items:center; gap:10px; height:56px; padding:0 16px; }
        .pub-head svg { width:24px; height:24px; color:var(--accent); flex:none; }
        .pub-head strong { font-weight:600; } .pub-head span { color:var(--muted); }
        .public .sheet { padding:28px 28px 32px; }
        .state h1 { font:500 28px/36px var(--mono); letter-spacing:-.02em; overflow-wrap:anywhere; }
        .state .lead { font-size:18px; line-height:26px; margin:12px 0 20px; overflow-wrap:anywhere; }
        .bill { margin-top:28px; padding-top:20px; border-top:1px solid var(--border); display:grid; gap:16px; }
        .bill .totals .grand { border-bottom:0; padding:12px 0 0; margin:4px 0 0; border-top:1px solid var(--border); }
        .bill .totals .grand dd { font-size:24px; line-height:30px; }
        .totals dt { overflow-wrap:anywhere; min-width:0; } .totals dd { text-align:right; flex:none; }
        .foot { max-width:640px; margin:0 auto 32px; padding:0 16px; color:var(--muted); font-size:13px; text-align:center; overflow-wrap:anywhere; }
        @media (max-width:1023.98px) { .public .sheet { margin:0 12px 16px; border:1px solid var(--border); border-radius:12px; padding:20px 16px 24px; } body { background:var(--ground); } }
    </style>
</head>
<body class="public no-shell">
<header class="pub-head"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 5h16a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7a2 2 0 0 1 2-2z"/><path d="M15.5 6.5v11" stroke="#fff" stroke-width="1.5" stroke-dasharray="1.6 1.9" stroke-linecap="round"/></svg><strong>{{ $store?->name ?: 'Anugerah' }}</strong><span>Servis Elektronik</span></header>
<div class="app"><div class="main"><main class="sheet state">
    <h1>{{ $order->number }}</h1>
    <p class="lead">{{ $order->category->name }} Anda
        @switch($order->status->value)
            @case('diterima') sudah <strong>diterima</strong>. @break
            @case('dikerjakan') sedang <strong>dikerjakan</strong>. @break
            @case('selesai') {{ $order->unrepairable ? 'tidak dapat diperbaiki dan' : 'sudah selesai dan' }} <strong>siap diambil</strong>. @break
            @case('diambil') {{ $order->unrepairable ? 'tidak dapat diperbaiki dan' : '' }} <strong>sudah diambil</strong>. @break
            @case('batal') <strong>dibatalkan</strong>. @break
        @endswitch
    </p>
    <ol class="track" aria-label="Tahap servis">
        @foreach(['diterima' => ['Diterima', $order->created_at], 'dikerjakan' => ['Dikerjakan', $order->assigned_at], 'selesai' => ['Selesai', $order->invoiced_on], 'diambil' => ['Diambil', $order->picked_up_on]] as $stage => [$label, $date])
            <li class="{{ $order->status->value === $stage ? 'now' : ($date ? 'done' : '') }}">{{ $label }}@if($date)<time>{{ $date->translatedFormat('j M') }}</time>@endif</li>
        @endforeach
    </ol>
    <div class="bill">
        <dl class="kv"><dt>Barang</dt><dd>{{ $order->category->name }}{{ $order->item_description ? ' '.$order->item_description : '' }}</dd><dt>Keluhan</dt><dd style="white-space:pre-line">{{ $order->complaint }}</dd>@if($order->invoiced_on)<dt>Tanggal nota</dt><dd>{{ $order->invoiced_on->translatedFormat('j M Y') }}</dd>@endif @if($order->cancelled_at)<dt>Tanggal batal</dt><dd>{{ $order->cancelled_at->translatedFormat('j M Y') }}</dd>@endif</dl>
        @if($order->invoiced_on)
            <dl class="totals">@foreach($items as $item)<div><dt>{{ $item->description }} × {{ $item->qty }}</dt><dd>Rp{{ number_format($item->qty * $item->price, 0, ',', '.') }}</dd></div>@endforeach<div class="grand"><dt>Total</dt><dd>Rp{{ number_format($items->sum(fn ($item) => $item->qty * $item->price), 0, ',', '.') }}</dd></div></dl>
            <p class="notice"><span>@if($order->warranty_until && $order->warranty_days) Garansi {{ $order->warranty_days }} hari, berlaku sampai <strong>{{ $order->warranty_until->translatedFormat('j M Y') }}</strong>. @else Tidak ada garansi. @endif</span></p>
            @if(\Illuminate\Support\Facades\Route::has('tracking.pdf'))<a class="btn primary block" href="{{ route('tracking.pdf', $order->tracking_token) }}" style="height:44px">Unduh nota (PDF)</a>@endif
        @endif
    </div>
</main></div></div>
<p class="foot">Saat mengambil, sebutkan nomor TTS atau tunjukkan struk Anda.@if($store?->address) {{ $store->address }}@endif @if($store?->phone) · {{ $store->phone }}@endif</p>
</body></html>
