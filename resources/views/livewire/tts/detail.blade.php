@php
    $stage = $order->status->value;
    $free = $financial && $financial['price'] === 0;
    $needsPayment = $financial && $financial['price'] > 0 && !$order->paid_at;
@endphp
<div class="tts-detail">
    <style>
        .tts-detail { overflow-wrap:anywhere; }
        .tts-detail .act-btn { background:var(--primary); color:var(--primary-ink); border-color:var(--primary); }
        .tts-detail .act-btn:hover,.tts-detail .act-btn[aria-expanded="true"] { background:var(--primary-hover); }
        .tts-detail .obj-head,.tts-detail .support-actions { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
        .tts-detail h1 { font:500 26px/34px var(--mono); }
        .tts-detail .title-row { margin-bottom:20px; align-items:center; }
        .tts-detail .summary-line { margin-top:8px; color:var(--muted); font-size:14px; line-height:1.6; }
        .tts-detail .service-overview { display:grid; grid-template-columns:1.2fr 1fr; border:1px solid var(--border); border-radius:var(--radius-card); }
        .tts-detail .overview-block { padding:20px 24px; min-width:0; }
        .tts-detail .overview-block + .overview-block { border-left:1px solid var(--border); }
        .tts-detail .overview-block h2,.tts-detail .handler-card > h2 { display:flex; gap:8px; align-items:center; color:var(--muted); font-size:14px; line-height:22px; font-weight:500; }
        .tts-detail .overview-block h2 .icon,.tts-detail .handler-card > h2 .icon { width:18px; height:18px; }
        .tts-detail .overview-value { margin-top:8px; font-size:20px; line-height:28px; font-weight:600; }
        .tts-detail .complaint { margin-top:14px; }
        .tts-detail .fact-label { display:block; font-size:13px; color:var(--muted); margin-bottom:4px; }
        .tts-detail .customer-phone { margin-top:6px; font-variant-numeric:tabular-nums; }
        .tts-detail .customer-address { color:var(--muted); font-size:14px; margin-top:6px; }
        .tts-detail .service-progress { padding:20px 0 24px; }
        .tts-detail .service-progress h2 { font-size:13px; font-weight:500; color:var(--muted); margin-bottom:10px; }
        .tts-detail .service-layout { display:grid; grid-template-columns:minmax(0,1fr) minmax(270px,320px); gap:24px; align-items:start; }
        .tts-detail .service-main,.tts-detail .service-side { min-width:0; }
        .tts-detail .service-layout.readonly-complete { grid-template-columns:minmax(0,1fr); }
        .tts-detail .service-main > .invoice-section:first-child { margin-top:0; }
        .tts-detail .work-panel { margin:0 0 20px; padding:24px; border:1px solid var(--border); border-radius:var(--radius-card); background:var(--raised); }
        .tts-detail .work-panel h2 { font-size:18px; line-height:26px; font-weight:600; }
        .tts-detail .work-panel > .muted { margin-top:6px; }
        .tts-detail .form { margin-top:16px; max-width:680px; }
        .tts-detail textarea.input { height:auto; min-height:88px; padding:12px; line-height:1.5; resize:vertical; background:var(--surface); }
        .tts-detail .inspection-panel { padding:20px; }
        .tts-detail .inspection-panel .form { margin-top:12px; max-width:none; gap:8px; }
        .tts-detail .inspection-panel .work-actions { margin-top:12px; gap:8px; }
        .tts-detail .work-actions { display:flex; gap:12px; flex-wrap:wrap; margin-top:20px; }
        .tts-detail .work-actions .btn,.tts-detail .support-actions .btn,.tts-detail .approval-next .btn { min-height:44px; }
        .tts-detail .approval-next { margin-top:12px; padding-top:12px; border-top:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:8px; flex-wrap:wrap; }
        .tts-detail .handler-card { border:1px solid var(--border); border-radius:var(--radius-card); padding:20px; }
        .tts-detail .handler-card > h2 { margin-bottom:16px; }
        .tts-detail .handler-name { font-size:18px; line-height:26px; font-weight:600; margin-bottom:4px; }
        .tts-detail .support-section { margin-top:20px; }
        .tts-detail .support-section > h2 { font-size:15px; line-height:24px; font-weight:600; margin-bottom:12px; }
        .tts-detail .handler-card .support-section { padding-top:16px; border-top:1px solid var(--border); }
        .tts-detail .handler-card .support-section .form { margin:0; }
        .tts-detail .support-actions .btn { width:100%; }
        .tts-detail .assignment-choices { display:grid; grid-template-columns:1fr; gap:12px; border:0; padding:0; margin:0; }
        .tts-detail .assignment-choices legend { font-weight:500; margin-bottom:8px; }
        .tts-detail .assignment-choices .check { display:flex; align-items:flex-start; gap:10px; padding:14px; border:1px solid var(--border); border-radius:var(--radius-control); background:var(--surface); cursor:pointer; }
        .tts-detail .assignment-choices .check:has(input:checked) { border-color:var(--accent); }
        .tts-detail .assignment-choices input { flex:none; margin-top:4px; }
        .tts-detail .assignment-choices small { display:block; font-size:13px; font-weight:400; color:var(--muted); margin-top:4px; }
        .tts-detail .receipt-actions { display:flex; gap:10px; flex-wrap:wrap; margin-top:24px; }
        .tts-detail .totals > div { flex-wrap:wrap; }
        .tts-detail .totals dd { max-width:100%; margin-left:auto; }
        .tts-detail .pickup-section { margin-top:16px; border-top:1px solid var(--border); padding-top:16px; }
        .tts-detail .pickup-section h3 { font-size:15px; font-weight:600; }
        .tts-detail .invoice-section { border:1px solid var(--border); border-radius:var(--radius-card); padding:20px; }
        @media(max-width:1023.98px) {
            .tts-detail .service-layout { grid-template-columns:1fr; gap:20px; }
            .tts-detail .work-panel { margin:0; padding:18px; }
            .tts-detail .approval-next { align-items:flex-start; flex-direction:column; }
            .tts-detail .overview-block { padding:18px; }
        }
        @media(max-width:639.98px) {
            .tts-detail .service-overview { grid-template-columns:1fr; }
            .tts-detail .overview-block + .overview-block { border-left:0; border-top:1px solid var(--border); }
            .tts-detail .overview-value { font-size:18px; line-height:26px; }
        }
    </style>
    <a class="crumb" href="{{ route('tts.index') }}"><x-ui.icon name="arrow-left" />Servis</a>
    <header class="title-row"><div>
        @if($success)<p class="muted">Tanda terima tersimpan</p>@endif
        <div class="obj-head"><h1>{{ $order->number }}</h1><x-ui.status :status="ucfirst($stage)" /></div>
        <p class="summary-line">Masuk {{ $order->created_at->format('d M Y, H.i') }} · dicatat oleh {{ $order->creator->name }}</p>
    </div>@if(!$success && $isAdmin)<div @class(['desktop-only' => $stage !== 'diambil'])>@include('livewire.tts.admin-actions', ['placement' => 'header', 'menuOnly' => true])</div>@else<x-ui.row-actions id="tts-document-actions" :label="$order->number">@include('livewire.tts.document-actions')</x-ui.row-actions>@endif</header>
    @if(session('tts-status'))<x-ui.notice type="success">{{ session('tts-status') }}</x-ui.notice>@endif
    @if($errors->any())<x-ui.notice type="error" x-init="$nextTick(() => $el.closest('.tts-detail').querySelector('[aria-invalid=true]')?.focus())">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</x-ui.notice>@endif

    <section class="service-overview" aria-label="Barang dan customer">
        <div class="overview-block"><h2><x-ui.icon name="package" />Barang servis</h2><p class="overview-value">{{ $order->category->name }}{{ $order->item_description ? ' · '.$order->item_description : '' }}</p><div class="complaint"><span class="fact-label">Keluhan pelanggan</span><p style="white-space:pre-line">{{ $order->complaint }}</p></div></div>
        <div class="overview-block"><h2><x-ui.icon name="user" />Pelanggan</h2><p class="overview-value">{{ $order->customer_name }}</p><p class="customer-phone">{{ $order->customer_phone }}</p><p class="customer-address" style="white-space:pre-line">{{ $order->customer_address ?: 'Alamat belum dicatat' }}</p>@if(!$isAdmin && $canEdit)<a class="btn" style="margin-top:12px" href="{{ route('tts.edit',$order->id) }}">Ubah TTS</a>@endif</div>
    </section>
    @unless($success)<section class="service-progress" aria-labelledby="service-progress-title"><h2 id="service-progress-title">Proses servis</h2><div class="support-body"><ol class="track {{ $order->inspection_started_at ? 'has-inspection' : '' }}" aria-label="Tahap servis">@foreach(['diterima' => ['Diterima',$order->created_at], ...($order->inspection_started_at ? ['pengecekan'=>['Pengecekan',$order->inspection_started_at]] : []), 'dikerjakan'=>['Dikerjakan',$order->work_started_at ?? ($order->inspection_started_at ? null : $order->assigned_at)], 'selesai'=>['Selesai',$order->invoiced_on], 'diambil'=>['Diambil',$order->picked_up_on]] as $key=>[$label,$date])<li class="{{ $key === $stage ? 'now' : ($date ? 'done' : '') }}">{{ $label }}@if($date)<time>{{ $date->format('d M Y') }}</time>@endif</li>@endforeach</ol></div></section>@endunless
    <div @class(['service-layout', 'readonly-complete' => $stage === 'diambil' && !$isAdmin])><div class="service-main">
    @if($success)
        <section class="work-panel"><h2>Tanda terima siap dibagikan</h2><p class="muted">Berikan struk kepada pelanggan. Penetapan teknisi dapat dilanjutkan dari detail TTS.</p>
            <div class="receipt-actions"><a class="btn primary" href="{{ route('tts.show', $order->id) }}">Lihat TTS</a><a class="btn" href="{{ route('tts.create') }}">Buat TTS lagi</a></div>
        </section>
    @elseif($stage !== 'diambil')
        <section @class(['work-panel', 'inspection-panel' => $stage === 'pengecekan']) aria-labelledby="current-work">
            <h2 id="current-work">{{ match($stage) { 'diterima' => 'Tetapkan teknisi', 'pengecekan' => 'Hasil pengecekan & rencana tindakan', 'dikerjakan' => 'Perbaikan sedang dikerjakan', 'selesai' => 'Barang siap diambil', 'diambil' => 'Servis telah selesai', default => 'Servis dibatalkan' } }}</h2>
            @if($stage !== 'pengecekan')<p class="muted">{{ match($stage) { 'diterima' => 'Pilih teknisi dan tentukan apakah barang perlu diperiksa terlebih dahulu.', 'dikerjakan' => 'Setelah pekerjaan selesai, isi rincian biaya dan terbitkan nota.', 'selesai' => 'Periksa pembayaran sebelum menyerahkan barang kepada pelanggan.', 'diambil' => 'Barang sudah diserahkan kepada pelanggan.', default => $order->cancel_reason ?: 'Pengerjaan untuk TTS ini dihentikan.' } }}</p>@endif
            @if($isAdmin)
                @if($stage === 'diterima')
                    @include('livewire.tts.technician-form')
                @elseif($stage === 'pengecekan')
                    <form id="inspection-form" class="form" wire:submit="saveInspection">
                        <textarea class="input" wire:model="inspection_result" rows="3" maxlength="8100" placeholder="Mis. baterai drop. Disarankan ganti baterai dan uji pengisian." aria-labelledby="current-work" aria-describedby="inspection-help{{ $errors->has('inspection_result') ? ' inspection-error' : '' }}" aria-invalid="{{ $errors->has('inspection_result') ? 'true' : 'false' }}"></textarea>
                        <p class="hint" id="inspection-help">Catatan tersimpan saat membuka WhatsApp. Kirim pesannya dari sana.</p>
                        @error('inspection_result')<p class="error-text" id="inspection-error" role="alert">{{ $message }}</p>@enderror

                    </form>
                @elseif(in_array($stage, ['selesai','diambil'], true))
                    @if($financial)<p style="margin-top:20px;font-size:24px;font-weight:600">Rp{{ number_format($financial['price'], 0, ',', '.') }}</p><p>{{ $free ? 'Gratis' : ($order->paid_at ? 'Lunas · '.\App\Support\Payment::label($order->payment_method,$order->payment_bank) : 'Belum dibayar') }}</p>@endif
                    @if($stage === 'selesai')
                        <form id="collect-order" class="form" wire:submit="collect">
                            <section class="pickup-section"><h3>Pengambilan barang</h3><div class="form">
                                <x-ui.date-picker name="picked_up_on" label="Tanggal diambil" :value="$picked_up_on" wire:model="picked_up_on" />
                                @if($needsPayment)<x-payment-fields :method="$payment_method" :bank="$payment_bank" prefix="collect" /><button class="btn" type="submit" wire:loading.attr="disabled" wire:target="collect">Bayar &amp; Diambil</button>@endif
                            </div></section>
                        </form>
                    @endif
                @endif
                <div class="work-actions">@if($stage === 'pengecekan')<button class="btn" type="submit" form="inspection-form" wire:loading.attr="disabled" wire:target="saveInspection,sendInspection,startWork"><span wire:loading.remove wire:target="saveInspection">Simpan catatan</span><span wire:loading wire:target="saveInspection">Menyimpan…</span></button>@endif<div class="desktop-only">@include('livewire.tts.admin-actions', ['placement' => 'work', 'primaryOnly' => true])</div></div>
                @if($stage === 'pengecekan')<div class="approval-next"><p>Pelanggan sudah setuju lewat WA?</p><button class="btn" type="button" wire:click="startWork" wire:loading.attr="disabled" wire:target="startWork,sendInspection,saveInspection">Mulai pengerjaan</button></div>@endif
            @else<p class="hint" style="margin-top:16px">Perubahan tahap dan penetapan teknisi dilakukan oleh admin toko.</p>@endif
        </section>
    @endif

    @if(!$success && $isAdmin && $order->invoiced_on)<section class="support-section invoice-section"><div class="support-body">@include('livewire.invoice.published')</div></section>@endif
    </div><aside class="service-side"><section class="handler-card"><h2><x-ui.icon name="wrench" />Penanganan servis</h2><span class="fact-label">Teknisi</span><p class="handler-name">{{ $order->technician?->name ?? 'Belum ditetapkan' }}</p>@if($order->assigned_at)<p class="small muted">Ditetapkan {{ $order->assigned_at->format('d M Y, H.i') }}</p>@endif
    @if(!$success && $isAdmin && in_array($stage,['pengecekan','dikerjakan'],true))<section class="support-section" aria-label="Ganti teknisi"><div class="support-body">@include('livewire.tts.technician-form')</div></section>@endif
    </section>
    @if(!$success && ($stage !== 'pengecekan' || !$isAdmin) && $order->inspectionNotes() !== '')<section class="support-section handler-card"><h2>Hasil pengecekan &amp; rencana tindakan</h2><p class="support-body" style="white-space:pre-line">{{ $order->inspectionNotes() }}</p></section>@endif
    @if($isAdmin && ($success || $stage !== 'pengecekan'))<div class="support-actions" style="margin-top:20px">@if($stage === 'batal')<a class="btn" href="{{ route('tts.whatsapp',$order->id) }}"><x-ui.icon name="message-circle" />WA pembatalan</a>@elseif($order->invoiced_on)<a class="btn" href="{{ route('tts.whatsapp',$order->id) }}"><x-ui.icon name="message-circle" />WA nota</a>@elseif(!$success && $order->inspectionNotes() !== '')<a class="btn" href="{{ route('tts.whatsapp',['id'=>$order->id,'inspection'=>1]) }}"><x-ui.icon name="message-circle" />WA hasil pengecekan</a>@else<a class="btn" href="{{ route('tts.whatsapp',$order->id) }}"><x-ui.icon name="message-circle" />WA tanda terima</a>@endif</div>@endif
    </aside></div>
    @if(!$success && $isAdmin && $stage !== 'diambil')<div class="actionbar">@include('livewire.tts.admin-actions',['placement'=>'mobile'])</div>@endif
    @if($isAdmin && ! $success && in_array($order->status->value, ['diterima', 'pengecekan', 'dikerjakan'], true))
        <dialog id="cancel-order" class="sheet-dialog" wire:ignore.self><div class="dialog-body"><h2>Batalkan {{ $order->number }}?</h2><p class="muted">TTS yang dibatalkan tidak masuk laporan dan tidak bisa diaktifkan lagi.</p><form class="form" wire:submit="cancel"><div class="field"><label for="cancel_reason">Alasan <span class="opt">opsional</span></label><input class="input" id="cancel_reason" wire:model="cancel_reason" maxlength="500" placeholder="Masukkan alasan pembatalan" aria-describedby="cancel-reason-help"><p class="hint" id="cancel-reason-help">Alasan ini akan tampil di link TTS pelanggan dan pesan WhatsApp pembatalan.</p></div><div class="dialog-actions"><button class="btn ghost" type="button" data-close>Kembali</button><button class="btn danger" type="submit" wire:loading.attr="disabled" wire:target="cancel">Batalkan TTS</button></div></form></div></dialog>
    @endif
    @if($isAdmin && ! $success && $financial && $financial['price'] > 0 && in_array($order->status->value, ['selesai', 'diambil'], true))
        <dialog id="payment-order" class="sheet-dialog" wire:ignore.self><div class="dialog-body"><h2>{{ $order->paid_at ? 'Ubah pembayaran' : 'Catat pembayaran' }}</h2><p class="muted">{{ $order->number }} · total {{ $financial ? 'Rp'.number_format($financial['price'], 0, ',', '.') : 'nota' }}</p><form class="form" wire:submit="recordPayment"><x-payment-fields :method="$payment_method" :bank="$payment_bank" prefix="payment" /><div class="dialog-actions"><button class="btn ghost" type="button" data-close>Kembali</button><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="recordPayment">Simpan pembayaran</button></div></form></div></dialog>
    @endif
</div>
