<div class="tts-detail">
    <style>
        .tts-detail { overflow-wrap:anywhere; } .tts-detail .obj-head { display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
        .tts-detail h1 { font:500 26px/34px var(--mono); letter-spacing:-.02em; } .tts-detail .track { margin:24px 0 32px; max-width:760px; }
        .tts-detail .blk + .blk { margin-top:32px; padding-top:24px; border-top:1px solid var(--rule); }
        .tts-detail .success-layout { display:grid; grid-template-columns:minmax(0,560px) minmax(0,1fr); gap:48px; align-items:start; }
        .tts-detail .done-head { display:grid; gap:8px; } .tts-detail .done-head h1 { font-size:34px; line-height:44px; }
        .tts-detail .ok { display:flex; align-items:center; gap:6px; color:var(--positive); font-weight:500; }
        .tts-detail .success-actions { display:grid; gap:8px; margin-top:24px; }
        .tts-detail .paper-bed { padding:24px; background:var(--raised); border-radius:var(--radius-card); display:grid; justify-items:center; }
        .tts-detail .paper { width:260px; background:white; padding:16px; border:1px solid var(--border); border-radius:4px; font-size:12px; line-height:17px; }
        .tts-detail .totals > div { flex-wrap:wrap; } .tts-detail .totals dt { min-width:0; } .tts-detail .totals dd { max-width:100%; margin-left:auto; text-align:right; }
        .tts-detail .paper hr { border:0; border-top:1px dashed var(--control); margin:8px 0; }
        @media(max-width:1023.98px) { .tts-detail h1 { font-size:24px; line-height:32px; } .tts-detail .done-head h1 { font-size:28px; line-height:36px; } .tts-detail .success-layout { display:block; } }
    </style>
    @unless($success)<a class="crumb" href="{{ route('tts.index') }}"><x-ui.icon name="arrow-left" />Servis</a>@endunless
    <div class="{{ $success ? 'success-layout' : '' }}">
        <div>
            @if($success)
                <div class="done-head"><span class="ok"><x-ui.icon name="circle-check" />Tanda terima tersimpan</span><h1>{{ $order->number }}</h1><p class="muted">Tulis atau sebutkan nomor ini ke customer. Admin akan menetapkan teknisi.</p></div>
            @else
                <div class="title-row"><div><div class="obj-head"><h1>{{ $order->number }}</h1><x-ui.status :status="ucfirst($order->status->value)" /></div><p class="sub">{{ $order->category->name }} · {{ $order->customer_name }} · masuk {{ $order->created_at->format('d M Y, H.i') }}</p></div>@if($isAdmin)<div class="actions desktop-only">@include('livewire.tts.admin-actions', ['placement' => 'desktop'])</div>@elseif($canEdit)<a class="btn primary desktop-only" href="{{ route('tts.edit', $order->id) }}">Ubah TTS</a>@endif</div>
            @endif
            <ol class="track" aria-label="Tahap servis">
                @foreach(['diterima' => ['Diterima', $order->created_at], 'dikerjakan' => ['Dikerjakan', $order->assigned_at], 'selesai' => ['Selesai', $order->invoiced_on], 'diambil' => ['Diambil', $order->picked_up_on]] as $stage => [$label, $date])
                    <li class="{{ $order->status->value === $stage ? 'now' : ($date ? 'done' : '') }}">{{ $label }}@if($date)<time>{{ $date->format(in_array($stage, ['selesai', 'diambil'], true) ? 'd M Y' : 'd M H.i') }}</time>@endif</li>
                @endforeach
            </ol>
            @if(session('tts-status'))<p class="notice" role="status">{{ session('tts-status') }}</p>@endif
            @if($errors->any())<div class="notice" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            @if($order->status === \App\Enums\ServiceOrderStatus::Batal)<p class="notice"><x-ui.status status="Batal" />{{ $order->cancelled_at ? ' · '.$order->cancelled_at->format('d M Y, H.i') : '' }} {{ $order->cancel_reason }}</p>@endif
            <div class="{{ $success ? '' : 'grid' }}"><div class="{{ $success ? '' : 'span-8' }}">
                <section class="blk" aria-label="Barang dan customer">@unless($success)<div class="section-head"><h2>Barang &amp; customer</h2></div>@endunless<dl class="kv" style="margin-top:12px">
                    @if($success)<dt>Customer</dt><dd>{{ $order->customer_name }}</dd><dt>Nomor HP</dt><dd class="num">{{ $order->customer_phone }}</dd>@endif
                    <dt>Barang</dt><dd>{{ $order->category->name }}{{ $order->item_description ? ' · '.$order->item_description : '' }}</dd><dt>Keluhan</dt><dd style="white-space:pre-line">{{ $order->complaint }}</dd>@unless($success)<dt>Customer</dt><dd>{{ $order->customer_name }}</dd><dt>Nomor HP</dt><dd class="num">{{ $order->customer_phone }}</dd>@endunless<dt>Alamat</dt><dd style="white-space:pre-line">{{ $order->customer_address ?: '—' }}</dd><dt>Dicatat oleh</dt><dd>{{ $order->creator->name }}</dd>
                </dl></section>
                @unless($success)<section class="blk"><div class="section-head"><h2>Riwayat</h2></div><ol class="timeline" style="margin-top:12px">@if($order->assigned_at)<li><strong>Ditetapkan ke {{ $order->technician?->name ?? '—' }}</strong><time>{{ $order->assigned_at->format('d M Y, H.i') }}</time></li>@endif<li><strong>TTS dibuat</strong><time>{{ $order->created_at->format('d M Y, H.i') }} · oleh {{ $order->creator->name }}</time></li></ol></section>@endunless
                @if($isAdmin && $order->invoiced_on)@include('livewire.invoice.published')@endif
            </div>@unless($success)<aside class="span-4"><section class="panel"><h2>Teknisi</h2><p style="margin-top:12px">{{ $order->technician?->name ?? 'Belum ditetapkan' }}</p>
                @if($isAdmin && in_array($order->status->value, ['diterima', 'dikerjakan'], true))
                    <form id="assign-technician" class="form" wire:submit="assign" style="margin-top:16px"><div class="field"><label for="technician_id">{{ $order->technician_id ? 'Ganti teknisi' : 'Pilih teknisi' }}</label><select class="select" id="technician_id" wire:model="technician_id"><option value="">Pilih teknisi aktif</option>@foreach($technicians as $technician)<option value="{{ $technician->id }}">{{ $technician->name }}</option>@endforeach</select></div>@if($order->status->value === 'dikerjakan')<button class="btn" type="submit" wire:loading.attr="disabled" wire:target="assign">Simpan teknisi</button>@endif</form>
                @endif
                @if($isAdmin && $order->status->value === 'selesai')<form class="form" id="collect-order" wire:submit="collect" style="margin-top:16px"><div class="field"><label for="picked_up_on">Tanggal diambil</label><input type="date" class="input" id="picked_up_on" wire:model="picked_up_on" min="1000-01-01" max="9999-12-31"></div></form>@endif@unless($canEdit)<p class="notice" style="margin-top:12px">TTS hanya bisa diedit oleh pembuatnya saat statusnya Diterima. Hubungi admin untuk perubahan.</p>@endunless</section></aside>@endunless</div>
            <div class="success-actions">
                @if($isAdmin && \Illuminate\Support\Facades\Route::has('tts.whatsapp'))<a class="btn" href="{{ route('tts.whatsapp', $order->id) }}">Kirim WA</a>@endif
                @if(\Illuminate\Support\Facades\Route::has('tts.print'))<a class="btn" href="{{ route('tts.print', $order->id) }}">Cetak struk</a>@endif
                @if($success)<a class="btn primary block" href="{{ route('tts.create') }}"><x-ui.icon name="plus" />Buat TTS lagi</a><a class="btn" href="{{ route('tts.show', $order->id) }}">Lihat TTS</a>@endif
            </div>
            @if(! $success && $isAdmin)<div class="actionbar">@include('livewire.tts.admin-actions', ['placement' => 'mobile'])</div>@elseif(! $success && $canEdit)<div class="actionbar"><a class="btn primary" href="{{ route('tts.edit', $order->id) }}">Ubah TTS</a></div>@endif
        </div>
        @if($success)<aside class="paper-bed desktop-only" aria-label="Tanda terima"><div class="paper"><p style="text-align:center;font-weight:600">{{ $storeName }}</p><hr><p style="text-align:center">TANDA TERIMA SERVIS</p><p class="id" style="text-align:center;font-size:17px">{{ $order->number }}</p><p style="text-align:center">{{ $order->created_at->format('d-m-Y H:i') }}</p><hr><p>{{ $order->customer_name }}</p><p>{{ $order->customer_phone }}</p><p>{{ $order->category->name }} · {{ $order->item_description }}</p><p>{{ $order->complaint }}</p><hr><p>Simpan tanda terima ini untuk pengambilan barang.</p></div></aside>@endif
    </div>
    @if($isAdmin && ! $success && in_array($order->status->value, ['diterima', 'dikerjakan'], true))
        <dialog id="cancel-order" class="sheet-dialog" wire:ignore.self><div class="dialog-body"><h2>Batalkan {{ $order->number }}?</h2><p class="muted">TTS yang dibatalkan tidak masuk laporan dan tidak bisa diaktifkan lagi.</p><form class="form" wire:submit="cancel"><div class="field"><label for="cancel_reason">Alasan <span class="opt">opsional</span></label><input class="input" id="cancel_reason" wire:model="cancel_reason" maxlength="500"></div><div class="dialog-actions"><button class="btn ghost" type="button" data-close>Kembali</button><button class="btn danger" type="submit" wire:loading.attr="disabled" wire:target="cancel">Batalkan TTS</button></div></form></div></dialog>
    @endif
</div>
