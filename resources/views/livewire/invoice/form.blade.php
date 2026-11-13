<div class="invoice-form" x-data>
    <style>
        .invoice-form { overflow-wrap:anywhere; } .invoice-form .totals dd { white-space:nowrap; } .invoice-form .totals > div { flex-wrap:wrap; } .invoice-form .summary { position:sticky; top:24px; display:grid; gap:16px; }
        .invoice-form .admin-note { display:inline-flex; align-items:center; gap:6px; color:var(--muted); font-size:var(--fs-small); }
        .invoice-form .warranty { display:grid; gap:16px; margin-top:32px; padding-top:24px; border-top:1px solid var(--rule); }
        .invoice-form .row2 { display:flex; align-items:center; gap:12px; flex-wrap:wrap; } .invoice-form .row2 .input { width:96px; }
        .invoice-form .error-text { margin-top:4px; }
        .invoice-form .negative-profit dd { color:var(--negative); } .invoice-form .notice { display:block; margin-bottom:16px; }
        @media(min-width:1024px) and (max-width:1199px) { .invoice-form form.grid > .span-8 { grid-column:span 12; } .invoice-form form.grid > .span-4 { grid-column:span 12; } .invoice-form .summary { max-width:560px; margin-top:16px; } }
        @media(max-width:1023.98px) { .invoice-form .line { grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:12px; } .invoice-form .line .segmented button { padding:0 16px; } .invoice-form .check { min-height:44px; } }
    </style>
    <a class="crumb" href="{{ route('tts.show', $orderId) }}"><x-ui.icon name="arrow-left" />{{ $order->number }}</a>
    <div class="title-row"><div><h1>{{ $correction ? 'Koreksi nota' : 'Rincian & nota' }}</h1><p class="sub">{{ $order->category->name }} · {{ $order->customer_name }} · {{ $order->technician?->name ?? 'Belum ada teknisi' }}</p></div></div>
    @if($errors->any())<div class="notice" role="alert" x-init="$nextTick(() => $root.querySelector('[aria-invalid=true]')?.focus())">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form wire:submit="save" id="invoice-form" class="grid" novalidate><div class="span-8">
        <div class="section-head"><h2>Pekerjaan</h2><span class="admin-note"><x-ui.icon name="lock" />Modal hanya terlihat oleh admin</span></div>
        <div class="lines-head" aria-hidden="true"><span></span><span>Jenis</span><span>Deskripsi</span><span>Qty</span><span>Harga jual</span><span>Modal</span></div>
        <div class="lines">
            @foreach($rows as $i => $line)
                <div class="line {{ ($line['type'] ?? '') === 'jasa' ? 'is-jasa' : '' }}" wire:key="invoice-line-{{ $i }}">
                    <div class="f-del"><button class="icon-btn" type="button" wire:click="removeRow({{ $i }})" aria-label="Hapus baris {{ $i + 1 }}"><x-ui.icon name="trash-2" /></button></div>
                    <div class="f-type"><div class="segmented" role="group" aria-label="Jenis baris {{ $i + 1 }}">@foreach(['part' => 'Part', 'jasa' => 'Jasa'] as $type => $label)<button type="button" wire:click="$set('rows.{{ $i }}.type', '{{ $type }}')" aria-pressed="{{ ($line['type'] ?? '') === $type ? 'true' : 'false' }}">{{ $label }}</button>@endforeach</div></div>
                    <label class="f-desc"><span class="f-label">Deskripsi</span><input class="input" wire:model="rows.{{ $i }}.description" maxlength="255" aria-label="Deskripsi baris {{ $i + 1 }}" aria-invalid="{{ $errors->has('rows.'.$i.'.description') ? 'true' : 'false' }}"></label>
                    <label class="f-qty"><span class="f-label">Qty</span><input class="input num" wire:model.live="rows.{{ $i }}.qty" inputmode="numeric" style="text-align:right" aria-label="Qty baris {{ $i + 1 }}" aria-invalid="{{ $errors->has('rows.'.$i.'.qty') ? 'true' : 'false' }}"></label>
                    @foreach(['price' => 'Harga jual', 'cost' => 'Modal'] as $field => $label)
                        <label class="f-{{ $field }}"><span class="f-label">{{ $label }}</span><span class="prefix"><span>Rp</span><input class="input money" type="text" inputmode="numeric" data-money="invoice-{{ $i }}-{{ $field }}" value="{{ is_numeric($line[$field] ?? null) ? number_format((int) $line[$field], 0, ',', '.') : '' }}" x-on:input="$wire.set('rows.{{ $i }}.{{ $field }}', $event.target.value.replace(/\D/g, ''))" aria-label="{{ $label }} baris {{ $i + 1 }}" aria-invalid="{{ $errors->has('rows.'.$i.'.'.$field) ? 'true' : 'false' }}"></span>@if($field === 'cost')<span class="na">—</span>@endif</label>
                    @endforeach
                    <p class="sub"><span>Subtotal</span><strong>{{ $totals && isset($totals['rows'][$i]) ? 'Rp'.number_format($totals['rows'][$i]['qty'] * $totals['rows'][$i]['price'], 0, ',', '.') : '—' }}</strong></p>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn ghost" wire:click="addRow" style="margin-top:8px;padding-left:0"><x-ui.icon name="plus" />Tambah baris</button>
        <section class="warranty"><h2>Garansi</h2><div class="field"><label for="warranty_days">Lama garansi</label><div class="row2"><input id="warranty_days" class="input num" inputmode="numeric" wire:model.live="warranty_days" aria-invalid="{{ $errors->has('warranty_days') ? 'true' : 'false' }}"><span>hari @if($warrantyUntil)· {{ (int) $warranty_days === 0 ? 'Tanpa garansi' : 'berlaku sampai '.$warrantyUntil->format('d M Y') }}@endif</span></div><p class="hint">Dihitung dari tanggal nota {{ $correction ? $order->invoiced_on?->format('d M Y') : 'hari ini' }}, bukan dari tanggal barang diambil.</p></div><label class="check"><input type="checkbox" wire:model="unrepairable"><span><strong>Tidak dapat diperbaiki</strong><span class="hint" style="display:block">Tercetak di nota. Biaya cek tetap boleh ditagih.</span></span></label></section>
    </div><aside class="span-4"><section class="panel summary"><h2>Ringkasan</h2><dl class="totals"><div class="grand"><dt>Total untuk customer</dt><dd>{{ $totals ? 'Rp'.number_format($totals['price'], 0, ',', '.') : '—' }}</dd></div><div><dt>Modal</dt><dd>{{ $totals ? 'Rp'.number_format($totals['cost'], 0, ',', '.') : '—' }}</dd></div><div @class(['laba' => $totals && $totals['profit'] > 0, 'negative-profit' => $totals && $totals['profit'] < 0])><dt>Laba</dt><dd>{{ $totals ? 'Rp'.number_format($totals['profit'], 0, ',', '.') : '—' }}</dd></div></dl><p class="small muted">Nota, struk, dan halaman status customer hanya menampilkan harga jual.</p><button type="submit" class="btn primary desktop-only" wire:loading.attr="disabled" wire:target="save">{{ $correction ? 'Simpan koreksi' : 'Terbitkan nota' }}</button></section></aside>
        <div class="actionbar"><div class="total"><span>Total</span><strong>{{ $totals ? 'Rp'.number_format($totals['price'], 0, ',', '.') : '—' }}</strong></div><button type="submit" class="btn primary" wire:loading.attr="disabled" wire:target="save"><span wire:loading.remove wire:target="save">{{ $correction ? 'Simpan koreksi' : 'Terbitkan nota' }}</span><span wire:loading wire:target="save">Menyimpan…</span></button></div>
    </form>
</div>
