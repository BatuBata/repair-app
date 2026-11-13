@php
    $money = fn ($n) => 'Rp'.preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', (string) $n);
    $total = $items->sum(fn ($line) => $line->qty * $line->price);
    $thermal = $format !== 'a5';
    $headerTop = $headerTop ?? 30;
@endphp
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $kind }} {{ $number }}</title>
<style>
@font-face { font-family: Plex; src: url('{{ ($format === 'html' ? asset('fonts/ibm-plex-sans-400.ttf') : public_path('fonts/ibm-plex-sans-400.ttf')) }}') format('truetype'); font-weight: normal; }
@font-face { font-family: Plex; src: url('{{ ($format === 'html' ? asset('fonts/ibm-plex-sans-600.ttf') : public_path('fonts/ibm-plex-sans-600.ttf')) }}') format('truetype'); font-weight: bold; }
@font-face { font-family: PlexMono; src: url('{{ ($format === 'html' ? asset('fonts/ibm-plex-mono-500.ttf') : public_path('fonts/ibm-plex-mono-500.ttf')) }}') format('truetype'); }
@page { margin: {{ $thermal ? '4mm 5mm 6mm' : $headerTop.'mm 12mm 14mm' }}; }
* { box-sizing: border-box; } body { font-family: Plex, sans-serif; font-size: {{ $thermal ? '8pt' : '9pt' }}; line-height: 1.35; color: #000; margin: 0; overflow-wrap: break-word; }
header { text-align: center; border-bottom: 1px solid #000; padding-bottom: 5px; margin-bottom: 8px; }
@if(! $thermal) header { position: fixed; top: -{{ $headerTop - 4 }}mm; left: 0; right: 0; } @endif
header img { max-width: 15mm; max-height: 12mm; } h1 { font-size: 11pt; margin: 4px 0; } p { margin: 3px 0; } .number { font-family: PlexMono, monospace; font-size: 11pt; } .center { text-align: center; } .section { border-bottom: 1px dashed #000; padding: 6px 0; } .pre { white-space: pre-line; } .qr { display: block; width: {{ $thermal ? '26mm' : '22mm' }}; height: {{ $thermal ? '26mm' : '22mm' }}; margin: 6px auto; }
table { width: 100%; border-collapse: collapse; } th, td { text-align: left; vertical-align: top; padding: 3px 2px; } .right { text-align: right; } .total { font-weight: bold; border-top: 1px solid #000; } .line { page-break-inside: avoid; } .toolbar { display: none; }
@if($format === 'html')
@media screen { body { background: #f2f5f6; padding: 16px; } .toolbar { display: flex; gap: 12px; max-width: 900px; margin: auto auto 20px; } .toolbar a, .toolbar button { font: 500 14px Plex, sans-serif; min-height: 44px; padding: 12px 16px; border: 1px solid #aeb9bc; border-radius: 4px; color: #15191a; background: #fff; text-decoration: none; cursor: pointer; } .toolbar button { background: #15191a; color: #fff; border-color: #15191a; } main { background: white; padding: 4mm 5mm 6mm; width: 58mm; margin: auto; } }
@media print { @page { margin: 4mm 5mm 6mm; } main { width: 48mm; max-width: 100%; } }
@endif
</style></head><body>
@if($format === 'html')<nav class="toolbar" aria-label="Cetak"><a href="{{ url()->previous() }}">Kembali</a><button type="button" onclick="window.print()">Cetak struk</button></nav>@endif
@if($format === 'html')<main>@endif<header>@if($thermal)@if($logo)<img src="{{ $logo }}" alt="">@endif<strong>{{ $store?->name ?: 'Anugerah' }}</strong><p class="pre">{{ $store?->address }}</p><p>{{ $store?->phone }}</p><h1>{{ $kind }}</h1><div class="number">{{ $number }}</div>@else<table><tr><td>@if($logo)<img src="{{ $logo }}" alt="">@endif<strong>{{ $store?->name ?: 'Anugerah' }}</strong><div>{{ $store?->phone }}</div></td><td class="right"><h1>{{ $kind }}</h1><div class="number">{{ $number }}</div></td></tr></table>@endif</header>@if($format !== 'html')<main>@endif
@if(! $thermal && $store?->address)<p class="pre">{{ $store->address }}</p>@endif
<section class="section"><p>Tanggal terima / jual: {{ $date->format('d/m/Y') }}</p>@if($invoiceDate)<p>Tanggal nota: {{ $invoiceDate->format('d/m/Y') }}</p>@endif
@if($customer)<p>Customer: {{ $customer }}</p>@endif @if($phone)<p>HP: {{ $phone }}</p>@endif
@if($item)<p>Barang: {{ $item }}</p>@endif @if($complaint)<p class="pre">Keluhan: {{ $complaint }}</p>@endif @if($technician)<p>Teknisi: {{ $technician }}</p>@endif</section>
@if($items->isNotEmpty())<section class="section"><strong>Rincian</strong>@foreach($items as $line)<div class="line"><p class="pre">{{ $line->description }}</p>@if($thermal)<p>{{ $line->qty }} × {{ $money($line->price) }}</p><p class="right">{{ $money($line->qty * $line->price) }}</p>@else<table><tr><td>{{ $line->qty }} × {{ $money($line->price) }}</td><td class="right">{{ $money($line->qty * $line->price) }}</td></tr></table>@endif</div> @endforeach @if($thermal)<div class="total"><p>Total</p><p class="right">{{ $money($total) }}</p></div>@else<table><tr class="total"><td>Total</td><td class="right">{{ $money($total) }}</td></tr></table>@endif</section>@endif
@if($kind !== 'Tanda terima servis')<section class="section">@if($unrepairable)<p><strong>Tidak dapat diperbaiki</strong></p>@endif<p>{{ $warrantyDays ? 'Garansi '.$warrantyDays.' hari · sampai '.$warrantyUntil?->format('d/m/Y') : 'Tanpa garansi' }}</p><p class="pre">{{ $store?->warranty_terms }}</p></section>@else<p class="center">Simpan struk ini untuk pengambilan barang.</p>@endif
@if($qr)<div class="line"><img class="qr" src="{{ $qr }}" alt="QR tracking"><p class="center">Pindai untuk melihat status servis</p></div>@endif</main></body></html>
