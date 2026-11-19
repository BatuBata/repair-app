@php
    $stage = $order->status->value;
    $free = $financial && $financial['price'] === 0;
    $needsPayment = $financial && $financial['price'] > 0 && !$order->paid_at;
@endphp
<div class="tts-detail">
    <style>
        .tts-detail { overflow-wrap:anywhere; max-width:1000px; margin-inline:auto; }
        .tts-detail .obj-head,.tts-detail .support-actions { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
        .tts-detail h1 { font:500 26px/34px var(--mono); }
        .tts-detail .summary-line { margin-top:8px; color:var(--muted); line-height:1.6; }
        .tts-detail .summary-line strong { color:var(--text); font-weight:500; }
        .tts-detail .work-panel { margin:24px 0; padding:24px; border:1px solid var(--border); border-radius:var(--radius-card); background:var(--raised); }
        .tts-detail .work-panel h2 { font-size:20px; line-height:28px; font-weight:600; }
        .tts-detail .work-panel > .muted { margin-top:6px; }
        .tts-detail .form { margin-top:20px; max-width:680px; }
        .tts-detail textarea.input { height:auto; min-height:150px; padding:14px; line-height:1.6; resize:vertical; background:var(--surface); }
        .tts-detail .work-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:20px; }
        .tts-detail .work-actions .btn,.tts-detail .support-actions .btn { min-height:44px; }
        .tts-detail .approval-next { margin-top:24px; padding-top:20px; border-top:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .tts-detail .approval-next .btn { min-height:44px; }
        .tts-detail .support-section { border-top:1px solid var(--border); }
        .tts-detail .support-section > summary { cursor:pointer; min-height:56px; padding:16px 0; font-weight:500; }
        .tts-detail .support-body { padding:0 0 20px; }
        .tts-detail .kv { margin-top:12px; }
        .tts-detail .document-links { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; max-width:620px; }
        .tts-detail .document-links .btn { min-height:44px; min-width:0; }
        .tts-detail .assignment-choices { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; border:0; padding:0; margin:0; }
        .tts-detail .assignment-choices legend { font-weight:500; margin-bottom:8px; }
        .tts-detail .assignment-choices .check { display:flex; align-items:flex-start; gap:10px; padding:14px; border:1px solid var(--border); border-radius:var(--radius-control); background:var(--surface); cursor:pointer; }
        .tts-detail .assignment-choices .check:has(input:checked) { border-color:var(--accent); }
        .tts-detail .assignment-choices input { flex:none; margin-top:4px; }
        .tts-detail .assignment-choices small { display:block; font-size:13px; font-weight:400; color:var(--muted); margin-top:4px; }
        .tts-detail .receipt-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:24px; }
        .tts-detail .totals > div { flex-wrap:wrap; } .tts-detail .totals dd { max-width:100%; margin-left:auto; }
        @media(max-width:1023.98px) { .tts-detail .work-panel { padding:18px; margin:20px 0; } .tts-detail .assignment-choices { grid-template-columns:1fr; } .tts-detail .approval-next { align-items:flex-start; flex-direction:column; } .tts-detail .work-actions .btn { flex:1 1 auto; } }
    </style>
    <a class="crumb" href="{{ route('tts.index') }}"><x-ui.icon name="arrow-left" />Servis</a>
    <header class="title-row"><div>
        @if($success)<p class="muted">Tanda terima tersimpan</p>@endif
        <div class="obj-head"><h1>{{ $order->number }}</h1><x-ui.status :status="ucfirst($stage)" /></div>
        <p class="summary-line"><strong>{{ $order->customer_name }}</strong> · {{ $order->category->name }}{{ $order->item_description ? ' · '.$order->item_description : '' }}<br>Teknisi: {{ $order->technician?->name ?? 'Belum ditetapkan' }}</p>
    </div>@if(!$success && $isAdmin)<div class="desktop-only">@include('livewire.tts.admin-actions', ['placement' => 'header', 'menuOnly' => true])</div>@endif</header>
    @if(session('tts-status'))<p class="notice" role="status">{{ session('tts-status') }}</p>@endif
    @if($errors->any())<div class="notice" role="alert" x-init="$nextTick(() => $el.closest('.tts-detail').querySelector('[aria-invalid=true]')?.focus())">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    @if($success)
        <section class="work-panel"><h2>Tanda terima siap dibagikan</h2><p class="muted">Berikan struk kepada pelanggan. Penetapan teknisi dapat dilanjutkan dari detail TTS.</p>
            <div class="receipt-actions"><a class="btn primary" href="{{ route('tts.show', $order->id) }}">Lihat TTS</a><a class="btn" href="{{ route('tts.create') }}">Buat TTS lagi</a></div>
        </section>
    @else
        <section class="work-panel" aria-labelledby="current-work">
            <h2 id="current-work">{{ match($stage) { 'diterima' => 'Tetapkan teknisi', 'pengecekan' => 'Catat hasil pengecekan', 'dikerjakan' => 'Perbaikan sedang dikerjakan', 'selesai' => 'Barang siap diambil', 'diambil' => 'Servis telah selesai', default => 'Servis dibatalkan' } }}</h2>
            <p class="muted">{{ match($stage) { 'diterima' => 'Pilih teknisi dan tentukan apakah barang perlu diperiksa terlebih dahulu.', 'pengecekan' => 'Tuliskan hasil cek dan tindakan yang disarankan, lalu tawarkan kepada pelanggan lewat WhatsApp.', 'dikerjakan' => 'Setelah pekerjaan selesai, isi rincian biaya dan terbitkan nota.', 'selesai' => 'Periksa pembayaran sebelum menyerahkan barang kepada pelanggan.', 'diambil' => 'Barang sudah diserahkan kepada pelanggan.', default => $order->cancel_reason ?: 'Pengerjaan untuk TTS ini dihentikan.' } }}</p>
            @if($isAdmin)
                @if($stage === 'diterima')
                    @include('livewire.tts.technician-form')
                @elseif($stage === 'pengecekan')
                    <form id="inspection-form" class="form" wire:submit="saveInspection">
                        <label class="field"><span>Hasil pengecekan &amp; rencana tindakan</span><textarea class="input" wire:model="inspection_result" rows="6" maxlength="8100" placeholder="Mis. baterai drop. Disarankan ganti baterai dan uji pengisian." aria-describedby="inspection-help{{ $errors->has('inspection_result') ? ' inspection-error' : '' }}" aria-invalid="{{ $errors->has('inspection_result') ? 'true' : 'false' }}"></textarea></label>
                        <p class="hint" id="inspection-help">Catatan disimpan sebelum WhatsApp dibuka. Pengiriman pesan dilakukan dari WhatsApp.</p>
                        @error('inspection_result')<p class="error-text" id="inspection-error" role="alert">{{ $message }}</p>@enderror
                        <div><button class="btn" type="submit" wire:loading.attr="disabled" wire:target="saveInspection,sendInspection,startWork"><span wire:loading.remove wire:target="saveInspection">Simpan catatan</span><span wire:loading wire:target="saveInspection">Menyimpan…</span></button></div>
                    </form>
                @elseif(in_array($stage, ['selesai','diambil'], true))
                    @if($financial)<p style="margin-top:20px;font-size:24px;font-weight:600">Rp{{ number_format($financial['price'], 0, ',', '.') }}</p><p>{{ $free ? 'Gratis' : ($order->paid_at ? 'Lunas · '.\App\Support\Payment::label($order->payment_method,$order->payment_bank) : 'Belum dibayar') }}</p>@endif
                    @if($stage === 'selesai')
                        <form id="collect-order" class="form" wire:submit="collect">
                            <details @if(!$needsPayment) open @endif><summary>Pengambilan barang</summary><div class="form">
                                <x-ui.date-picker name="picked_up_on" label="Tanggal diambil" :value="$picked_up_on" wire:model="picked_up_on" />
                                @if($needsPayment)<x-payment-fields :method="$payment_method" :bank="$payment_bank" prefix="collect" /><button class="btn" type="submit" wire:loading.attr="disabled" wire:target="collect">Bayar &amp; Diambil</button>@endif
                            </div></details>
                        </form>
                    @endif
                @endif
                <div class="work-actions desktop-only">@include('livewire.tts.admin-actions', ['placement' => 'work', 'primaryOnly' => true])</div>
                @if($stage === 'pengecekan')<div class="approval-next"><p>Pelanggan sudah setuju lewat WA?</p><button class="btn" type="button" wire:click="startWork" wire:loading.attr="disabled" wire:target="startWork,sendInspection,saveInspection">Mulai pengerjaan</button></div>@endif
            @else<p class="hint" style="margin-top:16px">Perubahan tahap dan penetapan teknisi dilakukan oleh admin toko.</p>@endif
        </section>
    @endif

    @if(!$success && $isAdmin && $order->invoiced_on)<details class="support-section"><summary>Lihat rincian nota</summary><div class="support-body">@include('livewire.invoice.published')</div></details>@endif
    @if(!$success && ($stage !== 'pengecekan' || !$isAdmin) && $order->inspectionNotes() !== '')<details class="support-section"><summary>Hasil pengecekan &amp; rencana tindakan</summary><p class="support-body" style="white-space:pre-line">{{ $order->inspectionNotes() }}</p></details>@endif
    @if(!$success && $isAdmin && in_array($stage,['pengecekan','dikerjakan'],true))<details class="support-section"><summary>Ganti teknisi · {{ $order->technician?->name }}</summary><div class="support-body">@include('livewire.tts.technician-form')</div></details>@endif
    <details class="support-section" @if($success) open @endif><summary>Detail barang &amp; pelanggan</summary><div class="support-body"><dl class="kv" aria-label="Barang dan customer"><dt>Barang</dt><dd>{{ $order->category->name }} · {{ $order->item_description }}</dd><dt>Keluhan</dt><dd style="white-space:pre-line">{{ $order->complaint }}</dd><dt>Customer</dt><dd>{{ $order->customer_name }}</dd><dt>Nomor HP</dt><dd>{{ $order->customer_phone }}</dd><dt>Alamat</dt><dd style="white-space:pre-line">{{ $order->customer_address ?: '—' }}</dd><dt>Dicatat oleh</dt><dd>{{ $order->creator->name }}</dd><dt>Diterima</dt><dd>{{ $order->created_at->format('d M Y, H.i') }}</dd></dl>@if(!$isAdmin && $canEdit)<a class="btn" href="{{ route('tts.edit',$order->id) }}">Ubah TTS</a>@endif</div></details>
    @unless($success)<details class="support-section"><summary>Riwayat servis</summary><div class="support-body"><ol class="track {{ $order->inspection_started_at ? 'has-inspection' : '' }}" aria-label="Tahap servis">@foreach(['diterima' => ['Diterima',$order->created_at], ...($order->inspection_started_at ? ['pengecekan'=>['Pengecekan',$order->inspection_started_at]] : []), 'dikerjakan'=>['Dikerjakan',$order->work_started_at ?? ($order->inspection_started_at ? null : $order->assigned_at)], 'selesai'=>['Selesai',$order->invoiced_on], 'diambil'=>['Diambil',$order->picked_up_on]] as $key=>[$label,$date])<li class="{{ $key === $stage ? 'now' : ($date ? 'done' : '') }}">{{ $label }}@if($date)<time>{{ $date->format('d M Y') }}</time>@endif</li>@endforeach</ol><p class="small muted" style="margin-top:16px">Ditetapkan ke {{ $order->technician?->name ?? '—' }}{{ $order->assigned_at ? ' · '.$order->assigned_at->format('d M Y, H.i') : '' }}</p></div></details>@endunless
    <details class="support-section"><summary>Cetak / unduh</summary><div class="support-body document-links"><a class="btn" href="{{ route('tts.print',$order->id) }}">Cetak struk</a><a class="btn" href="{{ route('tts.receipt.pdf',$order->id) }}">PDF struk</a>@if($isAdmin)<a class="btn" href="{{ route('tts.pdf',$order->id) }}">PDF A5</a>@endif</div></details>
    @if($isAdmin && ($success || $stage !== 'pengecekan'))<div class="support-actions" style="margin-top:20px">@if($order->invoiced_on)<a class="btn" href="{{ route('tts.whatsapp',$order->id) }}">WA nota</a>@elseif(!$success && $order->inspectionNotes() !== '')<a class="btn" href="{{ route('tts.whatsapp',['id'=>$order->id,'inspection'=>1]) }}">WA hasil pengecekan</a>@else<a class="btn" href="{{ route('tts.whatsapp',$order->id) }}">WA tanda terima</a>@endif</div>@endif
    @if(!$success && $isAdmin)<div class="actionbar">@include('livewire.tts.admin-actions',['placement'=>'mobile'])</div>@endif
    @if($isAdmin && ! $success && in_array($order->status->value, ['diterima', 'pengecekan', 'dikerjakan'], true))
        <dialog id="cancel-order" class="sheet-dialog" wire:ignore.self><div class="dialog-body"><h2>Batalkan {{ $order->number }}?</h2><p class="muted">TTS yang dibatalkan tidak masuk laporan dan tidak bisa diaktifkan lagi.</p><form class="form" wire:submit="cancel"><div class="field"><label for="cancel_reason">Alasan <span class="opt">opsional</span></label><input class="input" id="cancel_reason" wire:model="cancel_reason" maxlength="500" placeholder="Masukkan alasan pembatalan"></div><div class="dialog-actions"><button class="btn ghost" type="button" data-close>Kembali</button><button class="btn danger" type="submit" wire:loading.attr="disabled" wire:target="cancel">Batalkan TTS</button></div></form></div></dialog>
    @endif
    @if($isAdmin && ! $success && $financial && $financial['price'] > 0 && in_array($order->status->value, ['selesai', 'diambil'], true))
        <dialog id="payment-order" class="sheet-dialog" wire:ignore.self><div class="dialog-body"><h2>{{ $order->paid_at ? 'Ubah pembayaran' : 'Catat pembayaran' }}</h2><p class="muted">{{ $order->number }} · total {{ $financial ? 'Rp'.number_format($financial['price'], 0, ',', '.') : 'nota' }}</p><form class="form" wire:submit="recordPayment"><x-payment-fields :method="$payment_method" :bank="$payment_bank" prefix="payment" /><div class="dialog-actions"><button class="btn ghost" type="button" data-close>Kembali</button><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="recordPayment">Simpan pembayaran</button></div></form></div></dialog>
    @endif
</div>
