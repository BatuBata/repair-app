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
        .tts-detail textarea.input { height:auto; min-height:100px; resize:vertical; }
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
            <ol class="track {{ $order->inspection_started_at ? 'has-inspection' : '' }}" aria-label="Tahap servis">
                @foreach(['diterima' => ['Diterima', $order->created_at], ...($order->inspection_started_at ? ['pengecekan' => ['Pengecekan', $order->inspection_started_at]] : []), 'dikerjakan' => ['Dikerjakan', $order->work_started_at ?? ($order->inspection_started_at ? null : $order->assigned_at)], 'selesai' => ['Selesai', $order->invoiced_on], 'diambil' => ['Diambil', $order->picked_up_on]] as $stage => [$label, $date])
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
                @if(!$success && ($order->status->value === 'pengecekan' || filled($order->inspection_result) || filled($order->action_plan)))
                    <section class="blk" aria-labelledby="inspection-title"><h2 id="inspection-title">Hasil pengecekan</h2>
                        @if($isAdmin && $order->status->value === 'pengecekan')
                            <p class="small muted" style="margin-top:8px">Persetujuan pelanggan dilakukan lewat WA. Setelah disetujui, tekan Mulai pengerjaan.</p>
                            <form class="form" wire:submit="saveInspection" style="margin-top:16px">
                                <label class="field"><span>Hasil pengecekan</span><textarea class="input" wire:model="inspection_result" rows="4" maxlength="4000" placeholder="Mis. baterai drop dan tidak dapat menyimpan daya" aria-invalid="{{ $errors->has('inspection_result') ? 'true' : 'false' }}"></textarea></label>
                                <label class="field"><span>Rencana tindakan</span><textarea class="input" wire:model="action_plan" rows="4" maxlength="4000" placeholder="Mis. ganti baterai, lalu uji pengisian dan daya tahan" aria-invalid="{{ $errors->has('action_plan') ? 'true' : 'false' }}"></textarea></label>
                                <div class="actions" style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn" type="submit" wire:loading.attr="disabled" wire:target="saveInspection,sendInspection">Simpan hasil cek</button><button class="btn" type="button" wire:click="sendInspection" wire:loading.attr="disabled" wire:target="saveInspection,sendInspection">Simpan &amp; kirim WA</button></div>
                            </form>
                        @else
                            <dl class="kv" style="margin-top:16px"><dt>Hasil cek</dt><dd style="white-space:pre-line">{{ $order->inspection_result ?: 'Belum dicatat' }}</dd><dt>Rencana tindakan</dt><dd style="white-space:pre-line">{{ $order->action_plan ?: 'Belum dicatat' }}</dd></dl>
                            @if($isAdmin && filled($order->inspection_result) && filled($order->action_plan))<a class="btn" style="margin-top:16px" href="{{ route('tts.whatsapp', ['id' => $order->id, 'inspection' => 1]) }}">Kirim hasil cek via WA</a>@endif
                        @endif
                    </section>
                @endif
                @if($isAdmin && $order->invoiced_on)@include('livewire.invoice.published')@endif
            </div>@unless($success)<aside class="span-4"><section class="panel"><h2>Teknisi</h2><p style="margin-top:12px">{{ $order->technician?->name ?? 'Belum ditetapkan' }}</p>
                @if($isAdmin && in_array($order->status->value, ['diterima', 'pengecekan', 'dikerjakan'], true))
                    <form id="assign-technician" class="form" wire:submit="assign" style="margin-top:16px"><x-ui.search-select name="technician_id" :label="$order->technician_id ? 'Ganti teknisi' : 'Pilih teknisi'" :options="$technicians->map(fn ($technician) => ['value' => $technician->id, 'label' => $technician->name])" :value="$technician_id" placeholder="Cari teknisi aktif" wire:model="technician_id" />@if($order->status->value === 'diterima')<fieldset class="field"><legend>Setelah teknisi ditetapkan</legend><label class="check"><input type="radio" wire:model="assignment_stage" value="pengecekan"><span>Pengecekan dulu</span></label><label class="check"><input type="radio" wire:model="assignment_stage" value="dikerjakan"><span>Langsung dikerjakan</span></label></fieldset>@else<button class="btn" type="submit" wire:loading.attr="disabled" wire:target="assign">Simpan teknisi</button>@endif</form>
                @endif
                @if($isAdmin && $order->status->value === 'selesai')<form class="form" id="collect-order" wire:submit="collect" style="margin-top:16px"><x-ui.date-picker name="picked_up_on" label="Tanggal diambil" :value="$picked_up_on" wire:model="picked_up_on" />@if(!$order->paid_at && $financial && $financial['price'] > 0)<x-payment-fields :method="$payment_method" :bank="$payment_bank" prefix="collect" />@endif</form>@endif@unless($canEdit)<p class="notice" style="margin-top:12px">TTS hanya bisa diedit oleh pembuatnya saat statusnya Diterima. Hubungi admin untuk perubahan.</p>@endunless</section></aside>@endunless</div>
            <div class="success-actions">
                @if($isAdmin && \Illuminate\Support\Facades\Route::has('tts.whatsapp'))<a class="btn" href="{{ route('tts.whatsapp', $order->id) }}">Kirim WA</a>@endif
                @if(\Illuminate\Support\Facades\Route::has('tts.print'))<a class="btn" href="{{ route('tts.print', $order->id) }}">Cetak struk</a>@endif
                <a class="btn" href="{{ route('tts.receipt.pdf', $order->id) }}">PDF struk</a>@if($isAdmin)<a class="btn" href="{{ route('tts.pdf', $order->id) }}">PDF A5</a>@endif
                @if($success)<a class="btn primary block" href="{{ route('tts.create') }}"><x-ui.icon name="plus" />Buat TTS lagi</a><a class="btn" href="{{ route('tts.show', $order->id) }}">Lihat TTS</a>@endif
            </div>
            @if(! $success && $isAdmin)<div class="actionbar">@include('livewire.tts.admin-actions', ['placement' => 'mobile'])</div>@elseif(! $success && $canEdit)<div class="actionbar"><a class="btn primary" href="{{ route('tts.edit', $order->id) }}">Ubah TTS</a></div>@endif
        </div>
        @if($success)<aside class="paper-bed desktop-only" aria-label="Tanda terima"><div class="paper"><p style="text-align:center;font-weight:600">{{ $storeName }}</p><hr><p style="text-align:center">TANDA TERIMA SERVIS</p><p class="id" style="text-align:center;font-size:17px">{{ $order->number }}</p><p style="text-align:center">{{ $order->created_at->format('d-m-Y H:i') }}</p><hr><p>{{ $order->customer_name }}</p><p>{{ $order->customer_phone }}</p><p>{{ $order->category->name }} · {{ $order->item_description }}</p><p>{{ $order->complaint }}</p><hr><p>Simpan tanda terima ini untuk pengambilan barang.</p></div></aside>@endif
    </div>
    @if($isAdmin && ! $success && in_array($order->status->value, ['diterima', 'pengecekan', 'dikerjakan'], true))
        <dialog id="cancel-order" class="sheet-dialog" wire:ignore.self><div class="dialog-body"><h2>Batalkan {{ $order->number }}?</h2><p class="muted">TTS yang dibatalkan tidak masuk laporan dan tidak bisa diaktifkan lagi.</p><form class="form" wire:submit="cancel"><div class="field"><label for="cancel_reason">Alasan <span class="opt">opsional</span></label><input class="input" id="cancel_reason" wire:model="cancel_reason" maxlength="500" placeholder="Masukkan alasan pembatalan"></div><div class="dialog-actions"><button class="btn ghost" type="button" data-close>Kembali</button><button class="btn danger" type="submit" wire:loading.attr="disabled" wire:target="cancel">Batalkan TTS</button></div></form></div></dialog>
    @endif
    @if($isAdmin && ! $success && $financial && $financial['price'] > 0 && in_array($order->status->value, ['selesai', 'diambil'], true))
        <dialog id="payment-order" class="sheet-dialog" wire:ignore.self><div class="dialog-body"><h2>{{ $order->paid_at ? 'Ubah pembayaran' : 'Catat pembayaran' }}</h2><p class="muted">{{ $order->number }} · total {{ $financial ? 'Rp'.number_format($financial['price'], 0, ',', '.') : 'nota' }}</p><form class="form" wire:submit="recordPayment"><x-payment-fields :method="$payment_method" :bank="$payment_bank" prefix="payment" /><div class="dialog-actions"><button class="btn ghost" type="button" data-close>Kembali</button><button class="btn primary" type="submit" wire:loading.attr="disabled" wire:target="recordPayment">Simpan pembayaran</button></div></form></div></dialog>
    @endif
</div>
