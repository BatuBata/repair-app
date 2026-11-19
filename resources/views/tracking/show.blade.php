<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f2f5f6">
    <meta name="robots" content="noindex">
    <title>Status servis {{ $order->number }} — {{ $storeName }}</title>
    @vite(['resources/css/app.css', 'resources/css/layout.css'])
    <style>
        body.public { min-height:100svh; background:var(--ground); }
        .pub-head { width:min(920px,calc(100% - 32px)); min-height:72px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; gap:16px; }
        .pub-brand { display:flex; align-items:center; gap:10px; min-width:0; }
        .pub-brand .brand-mark { width:26px; height:26px; color:var(--accent); flex:none; }
        .pub-brand strong { display:block; font-weight:600; line-height:20px; }
        .pub-brand span,.pub-context { color:var(--muted); font-size:var(--fs-small); }
        .public .sheet { width:min(920px,calc(100% - 32px)); max-width:none; margin:0 auto 24px; padding:0; overflow:hidden; }
        .status-hero { padding:32px; }
        .status-meta { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:18px; }
        .status-meta .label { color:var(--muted); font-size:var(--fs-small); }
        .status-hero h1 { font:500 32px/40px var(--mono); letter-spacing:-.03em; overflow-wrap:anywhere; }
        .status-hero .lead { max-width:650px; margin-top:8px; font-size:20px; line-height:28px; }
        .status-hero .lead strong { font-weight:600; }
        .status-hero .track { margin-top:28px; }
        .status-hero .track li { gap:8px; font-size:13px; }
        .status-hero .track li::before { height:5px; }
        .tracking-body { display:grid; grid-template-columns:minmax(0,1.05fr) minmax(320px,.95fr); border-top:1px solid var(--border); }
        .tracking-section { min-width:0; padding:28px 32px 32px; }
        .tracking-section + .tracking-section { border-left:1px solid var(--border); }
        .section-title { display:flex; align-items:center; gap:8px; margin-bottom:20px; }
        .section-title .icon { width:18px; height:18px; color:var(--muted); }
        .section-title h2 { font-size:var(--fs-section); line-height:22px; }
        .detail-list { display:grid; gap:0; }
        .detail-list > div { display:grid; grid-template-columns:112px minmax(0,1fr); gap:16px; padding:12px 0; border-bottom:1px solid var(--rule); }
        .detail-list > div:first-child { padding-top:0; }
        .detail-list > div:last-child { padding-bottom:0; border-bottom:0; }
        .detail-list dt { color:var(--muted); }
        .detail-list dd { min-width:0; overflow-wrap:anywhere; }
        .detail-list .complaint { white-space:pre-line; }
        .next-step { display:grid; align-content:start; gap:12px; min-height:100%; }
        .next-step > .icon { width:28px; height:28px; color:var(--faint); }
        .next-step h2 { font-size:18px; line-height:26px; }
        .next-step p { color:var(--muted); }
        .invoice-lines { display:grid; gap:0; margin-bottom:16px; }
        .invoice-lines > div { display:flex; align-items:baseline; justify-content:space-between; gap:16px; padding:10px 0; border-bottom:1px solid var(--rule); }
        .invoice-lines dt { min-width:0; overflow-wrap:anywhere; }
        .invoice-lines dd { flex:none; text-align:right; font-variant-numeric:tabular-nums; }
        .invoice-total { display:flex; align-items:baseline; justify-content:space-between; gap:16px; padding:16px 0 20px; }
        .invoice-total dt { font-weight:600; }
        .invoice-total dd { font-size:26px; line-height:32px; font-weight:500; font-variant-numeric:tabular-nums; text-align:right; overflow-wrap:anywhere; }
        .warranty { margin-bottom:16px; }
        .warranty .icon { flex:none; }
        .download { height:44px; }
        .tracking-foot { width:min(920px,calc(100% - 32px)); margin:0 auto 32px; display:flex; align-items:flex-start; justify-content:space-between; gap:24px; color:var(--muted); font-size:13px; }
        .tracking-foot p { max-width:620px; }
        .store-contact { display:flex; flex-wrap:wrap; justify-content:flex-end; gap:8px 16px; }
        .store-contact span { display:inline-flex; align-items:center; gap:6px; }
        .store-contact .icon { width:15px; height:15px; flex:none; }
        @media(max-width:767.98px) {
            .pub-head { width:calc(100% - 32px); min-height:64px; }
            .pub-context { display:none; }
            .public .sheet { width:calc(100% - 24px); margin-bottom:20px; border-radius:12px; }
            .status-hero { padding:22px 20px 24px; }
            .status-meta { align-items:flex-start; margin-bottom:14px; }
            .status-hero h1 { font-size:26px; line-height:34px; }
            .status-hero .lead { font-size:18px; line-height:26px; }
            .status-hero .track { margin-top:24px; }
            .status-hero .track li { font-size:12px; line-height:18px; }
            .tracking-body { grid-template-columns:minmax(0,1fr); }
            .tracking-section { padding:22px 20px 24px; }
            .tracking-section + .tracking-section { border-left:0; border-top:1px solid var(--border); }
            .detail-list > div { grid-template-columns:96px minmax(0,1fr); gap:12px; }
            .next-step { min-height:0; }
            .invoice-total dd { font-size:22px; line-height:28px; }
            .tracking-foot { width:calc(100% - 32px); flex-direction:column; gap:12px; margin-bottom:24px; }
            .store-contact { justify-content:flex-start; }
        }
        @media(max-width:379.98px) {
            .detail-list > div { grid-template-columns:minmax(0,1fr); gap:4px; }
        }
    </style>
</head>
<body class="public no-shell">
@php
    $status = $order->status->value;
    $statusLabel = ucfirst($status);
    $stagePositions = ['diterima' => 0, 'pengecekan' => 1, 'dikerjakan' => 2, 'selesai' => 3, 'diambil' => 4];
    $headline = match($status) {
        'diterima' => 'Barang sudah kami terima',
        'pengecekan' => 'Barang sedang dalam pengecekan',
        'dikerjakan' => 'Servis sedang dikerjakan',
        'selesai' => $order->unrepairable ? 'Pemeriksaan servis telah selesai' : 'Servis selesai dan siap diambil',
        'diambil' => 'Barang sudah diambil',
        'batal' => 'Servis dibatalkan',
    };
    $summary = match($status) {
        'diterima' => 'Barang Anda sudah tercatat dan menunggu penanganan teknisi.',
        'pengecekan' => 'Teknisi memeriksa kerusakan dan tindakan yang diperlukan. Toko akan menghubungi Anda untuk membahas perbaikan.',
        'dikerjakan' => 'Teknisi sedang melakukan perbaikan barang Anda.',
        'selesai' => $order->unrepairable ? 'Barang tidak dapat diperbaiki dan dapat diambil di toko.' : 'Barang Anda sudah selesai dikerjakan dan dapat diambil di toko.',
        'diambil' => $order->unrepairable ? 'Barang yang tidak dapat diperbaiki sudah diserahkan.' : 'Barang yang selesai diservis sudah diserahkan.',
        'batal' => 'Pengerjaan untuk tanda terima ini telah dibatalkan.',
    };
    $nextTitle = match($status) {
        'diterima' => 'Menunggu penanganan',
        'pengecekan' => 'Menunggu hasil pengecekan',
        'dikerjakan' => 'Sedang dalam penanganan',
        'selesai' => 'Silakan ambil di toko',
        'diambil' => 'Servis telah selesai',
        'batal' => 'Pengerjaan dihentikan',
    };
    $nextCopy = match($status) {
        'diterima', 'pengecekan', 'dikerjakan' => 'Status pada halaman ini akan diperbarui mengikuti proses servis.',
        'selesai' => 'Saat mengambil, sebutkan nomor TTS atau tunjukkan struk Anda.',
        'diambil' => 'Simpan nota Anda untuk informasi servis dan garansi.',
        'batal' => 'Hubungi toko jika Anda memerlukan informasi lebih lanjut.',
    };
@endphp
<header class="pub-head">
    <div class="pub-brand"><x-ui.mark /><div><strong>{{ $storeName }}</strong><span>Status servis</span></div></div>
    <span class="pub-context">Status servis customer</span>
</header>
<main class="sheet state">
    <section class="status-hero" aria-labelledby="tracking-title">
        <div class="status-meta"><span class="label">Tanda terima servis</span><x-ui.status :status="$statusLabel" /></div>
        <h1 id="tracking-title">{{ $order->number }}</h1>
        <p class="lead"><strong>{{ $headline }}.</strong> {{ $summary }}</p>
        <ol class="track {{ $order->inspection_started_at ? 'has-inspection' : '' }}" aria-label="Tahap servis">
            @foreach(['diterima' => ['Diterima', $order->created_at], ...($order->inspection_started_at ? ['pengecekan' => ['Pengecekan', $order->inspection_started_at]] : []), 'dikerjakan' => ['Dikerjakan', $order->work_started_at ?? ($order->inspection_started_at ? null : $order->assigned_at)], 'selesai' => ['Selesai', $order->invoiced_on], 'diambil' => ['Diambil', $order->picked_up_on]] as $stage => [$label, $date])
                <li class="{{ $status === $stage ? 'now' : (($status === 'batal' && $date) || ($status !== 'batal' && $stagePositions[$stage] < ($stagePositions[$status] ?? -1)) ? 'done' : '') }}">{{ $label }}@if($date)<time>{{ $date->translatedFormat('j M') }}</time>@endif</li>
            @endforeach
        </ol>
    </section>
    <div class="tracking-body">
        <section class="tracking-section" aria-labelledby="item-heading">
            <div class="section-title"><x-ui.icon name="package" /><h2 id="item-heading">Rincian barang</h2></div>
            <dl class="detail-list">
                <div><dt>Barang</dt><dd>{{ $order->category->name }}{{ $order->item_description ? ' '.$order->item_description : '' }}</dd></div>
                <div><dt>Keluhan</dt><dd class="complaint">{{ $order->complaint }}</dd></div>
                <div><dt>Diterima</dt><dd>{{ $order->created_at->translatedFormat('j M Y, H.i') }} WITA</dd></div>
                @if($order->invoiced_on)<div><dt>Tanggal nota</dt><dd>{{ $order->invoiced_on->translatedFormat('j M Y') }}</dd></div>@endif
                @if($order->cancelled_at)<div><dt>Tanggal batal</dt><dd>{{ $order->cancelled_at->translatedFormat('j M Y') }}</dd></div>@endif
            </dl>
        </section>
        <section class="tracking-section" aria-labelledby="next-heading">
            @if($order->invoiced_on)
                <div class="section-title"><x-ui.icon name="receipt" /><h2 id="next-heading">Rincian nota</h2></div>
                <dl class="invoice-lines">@foreach($items as $item)<div><dt>{{ $item->description }} × {{ $item->qty }}</dt><dd>Rp{{ number_format($item->qty * $item->price, 0, ',', '.') }}</dd></div>@endforeach</dl>
                @php $subtotal = $items->sum(fn ($item) => $item->qty * $item->price); $total = $subtotal - $order->discount; @endphp
                @if($order->discount > 0)<dl class="invoice-lines"><div><dt>Subtotal</dt><dd>Rp{{ number_format($subtotal, 0, ',', '.') }}</dd></div><div><dt>Diskon</dt><dd>−Rp{{ number_format($order->discount, 0, ',', '.') }}</dd></div></dl>@endif
                <dl><div class="invoice-total"><dt>Total</dt><dd>Rp{{ number_format($total, 0, ',', '.') }}{{ $total === 0 ? ' (Gratis)' : '' }}</dd></div></dl>
                <p class="notice warranty"><x-ui.icon name="shield-check" /><span>@if($order->warranty_until && $order->warranty_days) Garansi {{ $order->warranty_days }} hari, berlaku sampai <strong>{{ $order->warranty_until->translatedFormat('j M Y') }}</strong>. @else Tidak ada garansi. @endif</span></p>
                @if(\Illuminate\Support\Facades\Route::has('tracking.pdf'))<a class="btn primary block download" href="{{ route('tracking.pdf', $order->tracking_token) }}"><x-ui.icon name="download" />Unduh nota PDF</a>@endif
            @else
                <div class="next-step"><x-ui.icon :name="$status === 'batal' ? 'ban' : 'clock'" /><h2 id="next-heading">{{ $nextTitle }}</h2>@if($status === 'batal' && trim((string) $order->cancel_reason) !== '')<dl class="detail-list"><div><dt>Alasan pembatalan</dt><dd class="complaint">{{ $order->cancel_reason }}</dd></div></dl>@endif<p>{{ $nextCopy }}</p></div>
            @endif
        </section>
    </div>
</main>
<footer class="tracking-foot">
    <p>Simpan nomor <strong>{{ $order->number }}</strong> untuk mengecek status servis ini kembali.</p>
    @if($store?->address || $store?->phone)<div class="store-contact">@if($store?->address)<span><x-ui.icon name="map-pin" />{{ $store->address }}</span>@endif @if($store?->phone)<span><x-ui.icon name="phone" />{{ $store->phone }}</span>@endif</div>@endif
</footer>
</body>
</html>
