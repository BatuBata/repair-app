<div class="dashboard-page">
    <style>
        .dashboard-page { overflow-wrap:anywhere; }
        .dashboard-page .title-row { margin-bottom:20px; }
        .dashboard-page .dashboard-grid { margin-top:20px; }
        .dashboard-page .dashboard-summary { display:grid; grid-template-columns:1.05fr .85fr 1.4fr; margin:0 0 24px; border:1px solid var(--border); border-radius:var(--radius-card); }
        .dashboard-page .dashboard-summary > div { display:grid; grid-template-columns:22px minmax(0,1fr); gap:4px 12px; align-content:start; min-width:0; padding:16px 20px; }
        .dashboard-page .dashboard-summary > div + div { border-left:1px solid var(--border); }
        .dashboard-page .dashboard-summary .icon { grid-row:1 / 4; width:20px; height:20px; margin-top:1px; color:var(--muted); }
        .dashboard-page .summary-label { color:var(--muted); }
        .dashboard-page .summary-value { font-size:var(--fs-kpi); line-height:34px; font-weight:400; letter-spacing:-.01em; font-variant-numeric:tabular-nums; }
        .dashboard-page .summary-value.money { font-size:clamp(18px,2vw,28px); overflow-wrap:normal; }
        .dashboard-page .summary-note { color:var(--muted); font-size:var(--fs-small); }
        .dashboard-page .dashboard-section-head { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; margin-bottom:10px; }
        .dashboard-page .dashboard-section-head > div { min-width:0; }
        .dashboard-page .dashboard-section-head h2 { font-size:var(--fs-section); line-height:22px; }
        .dashboard-page .dashboard-section-head p { margin-top:2px; color:var(--muted); font-size:var(--fs-small); }
        .dashboard-page .stage-line,.dashboard-page .panel-title { display:flex; align-items:center; gap:8px; }
        .dashboard-page .stage-line .icon { width:18px; height:18px; color:var(--muted); }
        .dashboard-page .lane { grid-template-columns:repeat(5,minmax(0,1fr)); }
        @media(max-width:1023.98px) { .dashboard-page .lane { grid-template-columns:repeat(2,minmax(0,1fr)); } .dashboard-page .lane > a:nth-child(odd) { border-left:0; } .dashboard-page .lane > a:nth-child(n+3) { border-top:1px solid var(--border); } .dashboard-page .lane > a:last-child { grid-column:1 / -1; } }
        .dashboard-page .lane > a:focus-visible { outline:2px solid var(--accent); outline-offset:2px; }
        .dashboard-page .cancelled { display:inline-flex; flex:0 0 auto; align-items:center; min-height:44px; gap:8px; color:var(--muted); white-space:nowrap; overflow-wrap:normal; }
        .dashboard-page .cancelled .icon { width:16px; height:16px; }
        .dashboard-page .priority-grid { align-items:stretch; }
        .dashboard-page .action-panel { display:flex; flex-direction:column; min-height:220px; }
        .dashboard-page .action-panel .rows { flex:1; }
        .dashboard-page .panel-title .icon { width:18px; height:18px; color:var(--muted); }
        .dashboard-page .panel > header { flex-wrap:wrap; }
        .dashboard-page .panel > header .link { min-height:44px; display:inline-flex; align-items:center; }
        .dashboard-page .rows.act-left .end { width:44px; align-self:flex-start; }
        .dashboard-page .rows.act-left .end .btn { width:44px; min-height:44px; padding:0; }
        .dashboard-page .rows .line1 { flex-wrap:wrap; }
        .dashboard-page .rows .line1 .name,.dashboard-page .rows .line2 { white-space:normal; overflow:visible; text-overflow:clip; }
        .dashboard-page .empty { flex:1; min-height:112px; padding:20px; border:0; background:var(--raised); }
        .dashboard-page .empty .icon { width:24px; height:24px; margin-bottom:4px; color:var(--faint); }
        .dashboard-page .empty p { margin-top:4px; }
        .dashboard-page .kpis { margin:0; }
        .dashboard-page .kpi { container-type:inline-size; grid-template-columns:minmax(0,1fr); gap:4px; }
        .dashboard-page .kpi-value { max-width:100%; text-align:left; line-height:34px; }
        .dashboard-page .kpi-value.money { font-size:clamp(16px,calc(160cqi / var(--amount-length)),28px); overflow-wrap:normal; }
        .dashboard-page .notice { margin-bottom:20px; }
        .dashboard-page .monthly-panel .kpis { margin:16px calc(var(--card-pad) * -1); border-top:1px solid var(--border); }
        .dashboard-page .monthly-panel .kpi { text-align:left; }
        .dashboard-page .loss { color:var(--negative); }
        .dashboard-page .monthly-heading { display:flex; align-items:flex-end; justify-content:space-between; gap:16px; }
        .dashboard-page .monthly-heading h2 { font-size:var(--fs-section); line-height:22px; }
        .dashboard-page .monthly-heading p { margin-top:2px; }
        .dashboard-page .monthly-heading .link { min-height:44px; display:inline-flex; align-items:center; }
        .dashboard-page .dashboard-chart { position:relative; padding-left:64px; }
        .dashboard-page .chart-plot { position:relative; height:220px; cursor:crosshair; touch-action:pan-y; }
        .dashboard-page .chart-plot:focus-visible { outline:2px solid var(--accent); outline-offset:4px; }
        .dashboard-page .chart-plot svg { width:100%; height:220px; display:block; overflow:visible; }
        .dashboard-page .chart-grid line { stroke:var(--grid); stroke-width:1; vector-effect:non-scaling-stroke; }
        .dashboard-page .chart-series { stroke:var(--chart-1); stroke-width:2; fill:none; vector-effect:non-scaling-stroke; }
        .dashboard-page .chart-y-axis { position:absolute; top:0; left:0; width:56px; height:220px; }
        .dashboard-page .chart-y-axis > span { position:absolute; right:0; max-width:56px; white-space:nowrap; transform:translateY(-50%); text-align:right; font-size:12px; line-height:16px; color:var(--axis-ink); overflow-wrap:normal; }
        .dashboard-page .chart-x-axis { position:relative; height:24px; margin-top:8px; color:var(--axis-ink); font-size:12px; }
        .dashboard-page .chart-x-axis > span { width:max-content; white-space:nowrap; position:absolute; transform:translateX(-50%); }
        .dashboard-page .chart-crosshair { position:absolute; top:0; height:220px; border-left:1px solid var(--grid); pointer-events:none; }
        .dashboard-page .chart-dot { position:absolute; width:7px; height:7px; border:1.5px solid var(--chart-1); background:var(--surface); border-radius:50%; transform:translate(-50%,-50%); pointer-events:none; }
        .dashboard-page .chart-tip { opacity:1; overflow-wrap:normal; position:absolute; top:8px; width:min(228px,calc(100% - 16px)); padding:8px; border:1px solid var(--grid); border-radius:6px; background:var(--surface); font-size:12px; line-height:18px; pointer-events:none; z-index:2; }
        .dashboard-page .dashboard-chart [x-cloak] { display:none!important; }
        @media(max-width:1023.98px) { .dashboard-page .dashboard-section-head,.dashboard-page .monthly-heading { align-items:flex-start; } .dashboard-page .action-panel { min-height:0; } .dashboard-page .monthly-panel .kpi { display:flex; flex-wrap:wrap; align-items:baseline; justify-content:space-between; gap:4px 16px; } .dashboard-page .monthly-panel .kpi-label { flex:1 1 120px; } .dashboard-page .monthly-panel .kpi-value { margin-left:auto; text-align:right; font-size:20px; line-height:26px; } .dashboard-page .monthly-panel .kpi-value.money { font-size:clamp(16px,calc(160cqi / var(--amount-length)),20px); } .dashboard-page .chart-x-axis .phone-hide { display:none; } }
        @media(max-width:599.98px) { .dashboard-page .dashboard-summary { grid-template-columns:minmax(0,1fr); } .dashboard-page .dashboard-summary > div { min-height:76px; padding:14px 16px; } .dashboard-page .dashboard-summary > div + div { border-left:0; border-top:1px solid var(--border); } }
        @media(max-width:1023.98px) { .dashboard-page .kpis { grid-auto-flow:row; grid-template-columns:minmax(0,1fr); } .dashboard-page .kpi + .kpi { border-left:0; border-top:1px solid var(--border); } }
    </style>
    <div class="title-row"><div><h1>Beranda</h1><p class="sub">{{ $today->locale('id')->isoFormat('dddd, D MMM YYYY') }} · WITA</p></div><div class="actions desktop-only"><a class="btn primary" href="{{ route('tts.create') }}"><x-ui.icon name="plus" />Buat TTS</a></div></div>
    @if(session('dashboard-status'))<p class="notice" role="status">{{ session('dashboard-status') }}</p>@endif
    @if($errors->any())<div class="notice" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <dl class="dashboard-summary" aria-label="Ringkasan hari ini">
        <div><x-ui.icon name="clock" /><dt class="summary-label">Perlu tindakan</dt><dd class="summary-value">{{ ($counts['diterima'] ?? 0) + ($counts['pengecekan'] ?? 0) + ($counts['selesai'] ?? 0) }}</dd><dd class="summary-note">Perlu teknisi, pengecekan, atau penyerahan</dd></div>
        <div><x-ui.icon name="clipboard-list" /><dt class="summary-label">TTS masuk hari ini</dt><dd class="summary-value">{{ $incoming }}</dd><dd class="summary-note">Sejak pukul 00.00 WITA</dd></div>
        <div><x-ui.icon name="wallet" /><dt class="summary-label">Omzet hari ini</dt><dd class="summary-value money">@foreach(explode('.', $revenue['total']) as $part){{ $part }}@unless($loop->last).<wbr>@endunless@endforeach</dd><dd class="summary-note">Servis {{ $revenue['service'] }} · Penjualan {{ $revenue['sales'] }}</dd></div>
    </dl>
    <section aria-labelledby="h-lane"><header class="dashboard-section-head"><div><h2 id="h-lane">Alur servis</h2><p>Pilih status untuk melihat pekerjaan yang perlu diteruskan.</p></div><a class="cancelled" href="{{ route('tts.index', ['status' => 'batal']) }}"><x-ui.icon name="ban" />Batal <span class="num">{{ $counts['batal'] ?? 0 }}</span></a></header><div class="lane">
        @foreach(['diterima' => ['Belum ada teknisi', 'perlu ditetapkan', 'user'], 'pengecekan' => ['Pengecekan', 'cek dan hubungi pelanggan', 'search'], 'dikerjakan' => ['Dikerjakan', 'dalam penanganan', 'wrench'], 'selesai' => ['Siap diambil', 'belum diserahkan', 'package-check'], 'diambil' => ['Diambil bulan ini', 'total '.$counts->get('diambil', 0).' diserahkan', 'circle-check']] as $status => [$label, $note, $icon])
            <a href="{{ route('tts.index', ['status' => $status]) }}"><span class="stage-line"><x-ui.icon :name="$icon" /><span class="stage">{{ $label }}</span></span><strong class="count">{{ $lane[$status]['count'] }}</strong><span class="note">{{ $note }}</span><span class="tags">@foreach($lane[$status]['tags'] as $tag)<span @class(['tag', 'late' => $tag['age'] >= 2 && $status !== 'diambil'])><b>{{ $tag['suffix'] }}</b>{{ $tag['age'] === 0 ? 'hari ini' : $tag['age'].' hari' }}</span>@endforeach</span></a>
        @endforeach
    </div></section>
    <div class="grid dashboard-grid priority-grid">
        <section class="panel span-7 action-panel" aria-labelledby="h-backlog"><header><div><h2 id="h-backlog" class="panel-title"><x-ui.icon name="package-check" />Siap diserahkan</h2><p class="small muted">Selesai belum diambil</p></div><a class="link" href="{{ route('tts.index', ['status' => 'selesai']) }}">Semua servis · {{ $counts['selesai'] ?? 0 }}<x-ui.icon name="chevron-right" /></a></header><ul class="rows act-left">
            @forelse($backlog as $order)
                <li wire:key="backlog-{{ $order->id }}"><div class="end"><x-ui.row-actions :id="'backlog-actions-'.$order->id" :label="$order->number"><a role="menuitem" href="{{ route('tts.show', $order->id) }}">Lihat TTS</a><a role="menuitem" href="{{ route('tts.whatsapp', $order->id) }}" target="_blank" rel="noopener">Kirim WA</a></x-ui.row-actions></div><div class="main-col"><div class="line1"><span class="id">{{ $order->number }}</span><x-ui.status status="Selesai" /></div><div class="line2">{{ $order->customer_name }} · {{ $order->category->name }} · nota {{ $order->invoiced_on?->format('d M Y') ?? '—' }}</div></div></li>
            @empty<li><div class="empty"><x-ui.icon name="package-check" /><strong>Belum ada barang yang siap diserahkan.</strong><p>Servis selesai akan muncul di sini.</p></div></li>@endforelse
        </ul></section>
        <section class="panel span-5 action-panel" aria-labelledby="h-warranty"><header><div><h2 id="h-warranty" class="panel-title" tabindex="-1"><x-ui.icon name="message-circle" />Garansi perlu dihubungi</h2><p class="small muted">Berakhir sampai {{ $today->addDays(3)->locale('id')->translatedFormat('d M Y') }}</p></div><span class="meta">{{ $reminders->count() }} pengingat</span></header><ul class="rows act-left">
            @forelse($reminders as $reminder)
                <li wire:key="warranty-{{ $reminder['kind'] }}-{{ $reminder['id'] }}"><div class="end"><x-ui.row-actions :id="'warranty-actions-'.$reminder['kind'].'-'.$reminder['id']" :label="$reminder['number']"><a role="menuitem" href="{{ $reminder['url'] }}" target="_blank" rel="noopener">Kirim WA</a><button type="button" role="menuitem" onclick="this.disabled=true; Livewire.find(@js($this->getId())).markContacted(@js($reminder['kind']), {{ $reminder['id'] }}).finally(() => { this.disabled=false; document.getElementById('h-warranty')?.focus(); })">Tandai dihubungi</button><a role="menuitem" href="{{ $reminder['detail'] }}">Lihat {{ $reminder['kind'] === 'service' ? 'TTS' : 'penjualan' }}</a></x-ui.row-actions></div><div class="main-col"><div class="line1"><span class="name">{{ $reminder['name'] }}</span><span @class(['due' => $reminder['days'] <= 1, 'muted' => $reminder['days'] > 1])>{{ match($reminder['days']) { 0 => 'berakhir hari ini', 1 => 'berakhir besok', default => 'berakhir '.$reminder['until']->locale('id')->translatedFormat('d M') } }}</span></div><div class="line2"><span class="id">{{ $reminder['number'] }}</span> · {{ $reminder['item'] }}</div></div></li>
            @empty<li><div class="empty"><x-ui.icon name="circle-check" /><strong>Tidak ada garansi yang perlu dihubungi.</strong><p>Pengingat muncul tiga hari sebelum berakhir.</p></div></li>@endforelse
        </ul></section>
        <section class="panel span-12 monthly-panel" aria-labelledby="h-monthly">
            <header class="monthly-heading"><div><h2 id="h-monthly">Performa bulan ini</h2><p class="small muted">1–{{ $today->locale('id')->translatedFormat('j M Y') }}, per hari · WITA</p></div>@if(\Illuminate\Support\Facades\Route::has('reports.index'))<a class="link" href="{{ route('reports.index') }}">Laporan lengkap<x-ui.icon name="chevron-right" /></a>@endif</header>
            <div class="kpis" role="tablist" aria-label="Seri grafik bulanan" x-data x-on:keydown="if(['ArrowRight','ArrowLeft','Home','End'].includes($event.key)){ $event.preventDefault(); const tabs=[...$el.querySelectorAll('[role=tab]')]; const i=tabs.indexOf(document.activeElement); const next=$event.key==='Home'?0:$event.key==='End'?tabs.length-1:(i+($event.key==='ArrowRight'?1:-1)+tabs.length)%tabs.length; tabs[next].focus(); tabs[next].click(); }">
                @foreach(['revenue' => 'Omzet', 'profit' => 'Laba kotor', 'invoices' => 'Nota servis'] as $key => $label)
                    <button class="kpi" type="button" id="tab-{{ $key }}" role="tab" aria-controls="monthly-chart" aria-selected="{{ $series === $key ? 'true' : 'false' }}" tabindex="{{ $series === $key ? '0' : '-1' }}" wire:key="tab-{{ $key }}" wire:click="selectSeries('{{ $key }}')"><span class="kpi-label">{{ $label }}</span><strong @class(['kpi-value', 'money' => $key !== 'invoices', 'loss' => str_contains($chart['totals'][$key], '-')]) style="--amount-length: {{ strlen($chart['totals'][$key]) }}">@foreach(explode('.', $chart['totals'][$key]) as $part){{ $part }}@unless($loop->last).<wbr>@endunless@endforeach</strong></button>
                @endforeach
            </div>
            <span class="sr-only" role="status" wire:loading wire:target="selectSeries">Memperbarui grafik…</span>
            <p class="small muted chart-scale" title="Kalikan angka sumbu dengan {{ $chart['scale_factor'] }} untuk nilai sebenarnya." style="margin-bottom:8px">Skala: {{ $chart['scale_label'] }}</p>
            <div id="monthly-chart" role="tabpanel" aria-labelledby="tab-{{ $series }}" wire:key="monthly-chart-{{ $series }}" class="dashboard-chart" x-data="{ active: null, labels: @js($chart['labels']) }">
                <div class="chart-y-axis" aria-hidden="true">@foreach($chart['ticks'] as $tick)<span style="top:{{ $tick['y'] }}px">@foreach(explode('.', $tick['label']) as $part){{ $part }}@unless($loop->last).<wbr>@endunless@endforeach</span>@endforeach</div>
                <div class="chart-plot" tabindex="0" role="group" aria-label="Grafik {{ ['revenue' => 'omzet', 'profit' => 'laba kotor', 'invoices' => 'nota servis'][$series] }}. Gunakan panah kiri dan kanan untuk memilih hari." x-on:keydown.right.prevent="active=Math.min(labels.length-1,(active ?? -1)+1)" x-on:keydown.left.prevent="active=Math.max(0,(active ?? labels.length)-1)" x-on:keydown.home.prevent="active=0" x-on:keydown.end.prevent="active=labels.length-1" x-on:blur="active=null" x-on:pointermove="if($event.pointerType !== 'touch') active=Math.max(0,Math.min(labels.length-1,Math.round(($event.clientX-$el.getBoundingClientRect().left)/$el.clientWidth*(labels.length-1))))" x-on:pointerleave="if($event.pointerType !== 'touch') active=null" x-on:pointercancel="active=null" x-on:pointerdown="if($event.pointerType === 'touch') active=Math.max(0,Math.min(labels.length-1,Math.round(($event.clientX-$el.getBoundingClientRect().left)/$el.clientWidth*(labels.length-1))))" x-on:click="active=Math.max(0,Math.min(labels.length-1,Math.round(($event.clientX-$el.getBoundingClientRect().left)/$el.clientWidth*(labels.length-1))))" x-on:click.outside="active=null">
                    <svg viewBox="0 0 1000 220" preserveAspectRatio="none" role="img" aria-label="{{ ['revenue' => 'Omzet', 'profit' => 'Laba kotor', 'invoices' => 'Nota servis'][$series] }} per hari bulan ini"><g class="chart-grid">@foreach($chart['ticks'] as $tick)<line x1="0" x2="1000" y1="{{ $tick['y'] }}" y2="{{ $tick['y'] }}" />@endforeach</g><polyline points="{{ $chart['polyline'] }}" class="chart-series" /></svg>
                    @foreach($chart['points'] as $i => $point)
                        <span class="chart-crosshair" x-cloak x-show="active === {{ $i }}" style="left:{{ $point['x'] / 10 }}%" aria-hidden="true"></span><span class="chart-dot" x-cloak x-show="active === {{ $i }}{{ count($chart['points']) === 1 ? ' || true' : '' }}" style="left:{{ $point['x'] / 10 }}%;top:{{ $point['y'] }}px" aria-hidden="true"></span>
                        <div class="chart-tip" role="tooltip" x-cloak x-show="active === {{ $i }}" style="left:clamp(8px,calc({{ $point['x'] / 10 }}% - 110px),max(8px,calc(100% - 236px)))"><strong>{{ $point['date_label'] }}</strong><p>@foreach(explode('.', $point['value']) as $part){{ $part }}@unless($loop->last).<wbr>@endunless@endforeach{{ $series === 'invoices' ? ' nota servis' : '' }}</p>@if($series !== 'invoices')<p class="muted">Servis @foreach(explode('.', $point['service']) as $part){{ $part }}@unless($loop->last).<wbr>@endunless@endforeach<br>Penjualan @foreach(explode('.', $point['sales']) as $part){{ $part }}@unless($loop->last).<wbr>@endunless@endforeach</p>@endif</div>
                    @endforeach
                </div>
                <div class="chart-x-axis" aria-hidden="true">@foreach($chart['points'] as $i => $point)<span @class(['phone-hide' => $i % max(1, (int) ceil((count($chart['points']) - 1) / 3)) !== 0]) style="left:{{ $point['x'] / 10 }}%;{{ $i === 0 && count($chart['points']) > 1 ? 'transform:none' : ($loop->last && count($chart['points']) > 1 ? 'transform:translateX(-100%)' : '') }}">{{ $point['day'] }}</span>@endforeach</div>
                <p class="sr-only" aria-live="polite" x-text="active === null ? 'Pilih hari pada grafik untuk melihat nilai.' : labels[active]"></p>
            </div>
            @if($chart['empty'])<p class="small muted">{{ ['revenue' => 'Belum ada omzet pada rentang ini.', 'profit' => 'Laba kotor pada rentang ini Rp0.', 'invoices' => 'Belum ada nota servis pada rentang ini.'][$series] }}</p>@endif
        </section>
    </div>
</div>
