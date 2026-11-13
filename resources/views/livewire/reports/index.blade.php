@php
    use App\Services\Reports\Report;
    $displayMoney = fn (string $amount) => new \Illuminate\Support\HtmlString(str_replace('.', '.<wbr>', e(Report::money($amount))));
@endphp
<div class="reports-page">
    @include('livewire.reports.styles')
    <div class="title-row"><div><h1>Laporan</h1><p class="sub">@if($report)Laba bersih <strong>{{ $displayMoney($report['net']) }}</strong> dari omzet {{ $displayMoney($report['revenue']) }}.@else Periksa periode laporan di bawah.@endif</p></div></div>
    <div class="period" role="group" aria-label="Periode laporan">
        <div class="segmented" role="group" aria-label="Pilihan cepat">
            @foreach(['today' => 'Hari ini', 'week' => '7 hari', 'month' => 'Bulan ini'] as $value => $label)
                @php $quickFrom = match($value) { 'today' => now('Asia/Makassar')->toDateString(), 'week' => now('Asia/Makassar')->subDays(6)->toDateString(), default => now('Asia/Makassar')->startOfMonth()->toDateString() }; @endphp
                <button type="button" wire:click="period('{{ $value }}')" aria-pressed="{{ $from === $quickFrom && $to === now('Asia/Makassar')->toDateString() ? 'true' : 'false' }}">{{ $label }}</button>
            @endforeach
        </div>
        <div class="report-dates">
            @foreach(['from' => 'Dari tanggal', 'to' => 'Sampai tanggal'] as $field => $label)
                <label class="field"><span class="small muted">{{ $label }}</span><input class="input num" type="date" min="1000-01-01" max="9999-12-31" wire:model.live="{{ $field }}" aria-invalid="{{ $filterErrors->has($field) ? 'true' : 'false' }}" aria-describedby="report-{{ $field }}-error">@if($filterErrors->has($field))<span class="error-text" id="report-{{ $field }}-error" role="alert">{{ $filterErrors->first($field) }}</span>@endif</label>
            @endforeach
        </div>
    </div>
    @if($filterErrors->has('type'))<p class="notice" role="alert">{{ $filterErrors->first('type') }}</p>@endif
    @if($report)
        <div class="grid" wire:loading.class="report-loading">
            <section class="span-5" aria-labelledby="report-statement">
                <div class="section-head"><h2 id="report-statement">Laba rugi</h2><span class="meta">{{ \Carbon\CarbonImmutable::parse($from)->format('d/m/Y') }}–{{ \Carbon\CarbonImmutable::parse($to)->format('d/m/Y') }} · WITA</span></div>
                <dl class="statement">
                    <div class="{{ strlen($report['service']) > 15 ? 'statement-long' : '' }}"><dt>Omzet servis</dt><dd>{{ $displayMoney($report['service']) }}</dd></div>
                    <div class="{{ strlen($report['sales']) > 15 ? 'statement-long' : '' }}"><dt>Omzet penjualan</dt><dd>{{ $displayMoney($report['sales']) }}</dd></div>
                    <div class="sum {{ strlen($report['revenue']) > 15 ? 'statement-long' : '' }}"><dt>Omzet</dt><dd>{{ $displayMoney($report['revenue']) }}</dd></div>
                    <div class="{{ strlen($report['cost']) > 15 ? 'statement-long' : '' }}"><dt>Modal</dt><dd>{{ $report['cost'] === '0' ? '' : '−' }}{{ $displayMoney($report['cost']) }}</dd></div>
                    <div class="sum {{ strlen($report['gross']) > 15 ? 'statement-long' : '' }}"><dt>Laba kotor</dt><dd>{{ $displayMoney($report['gross']) }}</dd></div>
                    <div class="{{ strlen($report['expenses']) > 15 ? 'statement-long' : '' }}"><dt>Pengeluaran</dt><dd>{{ $report['expenses'] === '0' ? '' : '−' }}{{ $displayMoney($report['expenses']) }}</dd></div>
                    <div class="final {{ strlen($report['net']) > 15 ? 'statement-long' : '' }}"><dt>Laba bersih</dt><dd>{{ $displayMoney($report['net']) }}</dd></div>
                </dl>
                <p class="small muted report-note">TTS batal dan nota yang belum terbit tidak dihitung.</p>
                <p class="small muted">Restok (informasi): <span class="num">{{ $displayMoney($report['restock']) }}</span>. Tidak mengurangi laba.</p>
            </section>
            <section class="span-7" aria-labelledby="report-daily">
                <div class="section-head"><h2 id="report-daily">Omzet per hari</h2><span class="meta">Tertinggi {{ $displayMoney($report['highest']) }}</span></div>
                <p class="small muted">Rata-rata periode {{ $displayMoney($report['average']) }}/hari @if($report['averageRemainder'] !== '0') (dibulatkan ke bawah)@endif · {{ $report['dayCount'] }} hari</p>
                <p class="small muted report-note">Grafik {{ $report['days'][0]['date'] }}–{{ $report['days'][count($report['days']) - 1]['date'] }} · geser, ketuk, atau fokuskan hari untuk rincian. @if($report['axisTrillions']) Sumbu T = triliun rupiah. @endif</p>
                <div class="daily-chart" aria-label="Grafik omzet harian">
                    <div class="chart-axis" aria-hidden="true">@foreach($report['ticks'] as $tick)<span style="bottom:{{ $tick['bottom'] }}%">{{ $tick['label'] }}</span>@endforeach</div>
                    <div class="chart-scroll" x-init="$nextTick(() => $el.scrollLeft = $el.scrollWidth)" tabindex="0" aria-label="Geser grafik untuk melihat semua hari">
                        <div class="chart-plot" style="--days:{{ count($report['days']) }}">
                            @foreach($report['ticks'] as $tick)<div class="chart-grid" style="bottom:{{ $tick['bottom'] }}%" aria-hidden="true"></div>@endforeach
                            <div class="chart-average" style="bottom:{{ $report['averageHeight'] }}%" aria-hidden="true"></div>
                            <div class="chart-columns">
                                @foreach($report['days'] as $day)
                                    <div class="chart-day" x-data="{ open: false }" wire:key="day-{{ $day['date'] }}" x-on:mouseenter="open = true" x-on:mouseleave="open = false" x-on:focusin="open = true" x-on:focusout="open = false" x-on:keydown.escape="open = false">
                                        <button type="button" class="chart-hit" x-on:click="open = true" aria-label="{{ $day['date'] }}, omzet {{ Report::money($day['amount']) }}, servis {{ Report::money($day['servis']) }}, penjualan {{ Report::money($day['jual']) }}" aria-describedby="tip-{{ $day['date'] }}" x-bind:aria-expanded="open.toString()">
                                            @if($day['amount'] !== '0')<span class="daily-bar {{ $day['today'] ? 'today' : '' }}" style="height:{{ $day['height'] }}%" aria-hidden="true"></span>@endif
                                            <span class="day-label {{ $day['today'] ? 'today' : '' }}">{{ $day['label'] }}</span>
                                        </button>
                                        <div class="daily-tip" id="tip-{{ $day['date'] }}" role="tooltip" x-cloak x-show="open"><span class="muted">{{ $day['date'] }}</span><strong>{{ $displayMoney($day['amount']) }}</strong><span>Servis {{ $displayMoney($day['servis']) }}</span><span>Penjualan {{ $displayMoney($day['jual']) }}</span></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @if($report['lastPage'] > 0)<div class="chart-paging"><button type="button" class="btn sm" wire:click="$set('chartPage', {{ max(0, $report['page'] - 1) }})" @disabled($report['page'] === 0)>Hari sebelumnya</button><span class="small muted">{{ $report['page'] + 1 }} / {{ $report['lastPage'] + 1 }}</span><button type="button" class="btn sm" wire:click="$set('chartPage', {{ $report['page'] + 1 }})" @disabled($report['page'] === $report['lastPage'])>Hari berikutnya</button></div>@endif
                @if($report['revenue'] === '0')<p class="small muted">Tidak ada omzet pada rentang ini.</p>@endif
            </section>
            <section class="span-12 report-transactions" aria-labelledby="report-transactions">
                <div class="section-head"><h2 id="report-transactions">Transaksi</h2><span class="meta">{{ count($rows) }} transaksi</span></div>
                <div class="tabs" role="group" aria-label="Jenis transaksi">
                    @foreach(['semua' => 'Semua', 'servis' => 'Servis', 'jual' => 'Penjualan', 'keluar' => 'Pengeluaran'] as $value => $label)<button type="button" wire:click="$set('type', '{{ $value }}')" aria-pressed="{{ $type === $value ? 'true' : 'false' }}">{{ $label }}</button>@endforeach
                </div>
                <table class="table report-table"><thead><tr><th class="act"><span class="sr-only">Aksi</span></th><th class="desktop-only">Tanggal</th><th>Nomor</th><th class="desktop-only">Jenis</th><th>Keterangan</th><th class="n">Jumlah</th><th class="n desktop-only">Modal</th><th class="n desktop-only">Laba</th></tr></thead><tbody>
                    @forelse($rows as $row)
                        <tr wire:key="report-{{ $row['key'] }}"><td class="act"><button type="button" class="btn sm report-detail" id="report-view-{{ $row['key'] }}" wire:click="$set('detail', '{{ $row['key'] }}')" aria-label="Rincian {{ $row['number'] }}"><x-ui.icon name="eye" /></button></td><td class="desktop-only">{{ \Carbon\CarbonImmutable::parse($row['date'])->translatedFormat('j M') }}</td><td class="report-number">@if($row['url'])<a href="{{ $row['url'] }}">{{ $row['number'] }}</a>@else{{ $row['number'] }}@endif</td><td class="desktop-only">{{ ['servis' => 'Servis', 'jual' => 'Penjualan', 'keluar' => 'Pengeluaran'][$row['type']] }}</td><td class="report-description">{{ $row['description'] }}<span class="mobile-only small muted">{{ \Carbon\CarbonImmutable::parse($row['date'])->format('d/m/Y') }} · {{ ['servis' => 'Servis', 'jual' => 'Penjualan', 'keluar' => 'Pengeluaran'][$row['type']] }}</span></td><td class="n report-amount">{{ $displayMoney($row['amount']) }}</td><td class="n desktop-only">{{ $displayMoney($row['cost']) }}</td><td class="n desktop-only">{{ $displayMoney($row['profit']) }}</td></tr>
                    @empty<tr><td colspan="8" class="report-empty"><div class="empty"><strong>Tidak ada transaksi</strong><p>Ubah tanggal atau jenis transaksi.</p></div></td></tr>@endforelse
                </tbody><tfoot><tr><td colspan="5">Total {{ $type === 'semua' ? 'semua transaksi' : ['servis' => 'servis', 'jual' => 'penjualan', 'keluar' => 'pengeluaran'][$type] }}</td><td class="n desktop-only">{{ $displayMoney($totals['amount']) }}</td><td class="n mobile-only">{{ $type === 'semua' ? 'Laba bersih ' : '' }}{{ $displayMoney($type === 'semua' ? $totals['profit'] : $totals['amount']) }}</td><td class="n desktop-only">{{ $displayMoney($totals['cost']) }}</td><td class="n desktop-only">{{ $type === 'semua' ? 'Laba bersih ' : '' }}{{ $displayMoney($totals['profit']) }}</td></tr></tfoot></table>
                <p class="small muted report-note">Jumlah pengeluaran bertanda minus. Laba total seluruh transaksi sama dengan laba bersih.</p>
            </section>
        </div>
        @if($selected)
            <dialog class="sheet-dialog report-dialog" x-init="$el.showModal()" x-on:cancel.prevent="$el.close(); $wire.set('detail', null).then(() => document.getElementById('report-view-{{ $selected['key'] }}')?.focus())" aria-labelledby="report-detail-title"><div class="dialog-body"><div class="section-head"><h2 id="report-detail-title">{{ $selected['number'] }}</h2><button type="button" class="btn ghost" x-on:click="$el.closest('dialog').close(); $wire.set('detail', null).then(() => document.getElementById('report-view-{{ $selected['key'] }}')?.focus())">Tutup</button></div><p class="muted">{{ $selected['date'] }} · {{ $selected['description'] }}</p><dl class="statement">@foreach($selected['lines'] as $line)<div><dt>{{ $line['description'] }} × {{ $line['qty'] }}<span class="small muted report-line-cost">Modal {{ $displayMoney($line['cost']) }}</span></dt><dd>{{ $displayMoney($line['amount']) }}</dd></div>@endforeach<div class="sum"><dt>Jumlah</dt><dd>{{ $displayMoney($selected['amount']) }}</dd></div><div><dt>Modal</dt><dd>{{ $displayMoney($selected['cost']) }}</dd></div><div><dt>Laba</dt><dd>{{ $displayMoney($selected['profit']) }}</dd></div></dl>@if($selected['url'])<a class="btn" href="{{ $selected['url'] }}">Buka transaksi</a>@endif</div></dialog>
        @endif
    @else
        <p class="notice" role="status">Laporan belum ditampilkan. Perbaiki tanggal atau jenis transaksi.</p>
    @endif
    <span class="sr-only" role="status" wire:loading>Memuat laporan…</span>
</div>
