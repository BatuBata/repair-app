<div class="tts-list">
    <style>.tts-list { overflow-wrap:anywhere; } .tts-list .c-id { white-space:nowrap; } .tts-list .tabs button.active { color:var(--text); font-weight:500; } .tts-list .tabs button.active::after { content:""; position:absolute; left:0; right:0; bottom:-1px; height:2px; background:var(--accent); } .tts-list .pagination { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-top:24px; } @media(max-width:1023.98px) { .tts-list .tabs button { min-height:44px; min-width:44px; } .tts-list .c-id a { display:inline-flex; align-items:center; min-height:44px; } .tts-list .c-st { align-self:center; } }</style>
    <div class="title-row"><div><h1>Servis</h1><p class="sub">Tanda terima servis</p></div><a class="btn primary" href="{{ route('tts.create') }}"><x-ui.icon name="plus" />Buat TTS</a></div>
    <div class="tabs" aria-label="Status servis">
        @foreach(['' => 'Semua', 'diterima' => 'Diterima', 'dikerjakan' => 'Dikerjakan', 'selesai' => 'Selesai', 'diambil' => 'Diambil', 'batal' => 'Batal'] as $value => $label)
            <button type="button" wire:click="$set('status', '{{ $value }}')" class="{{ $status === $value ? 'active' : '' }}" aria-pressed="{{ $status === $value ? 'true' : 'false' }}">{{ $label }}</button>
        @endforeach
    </div>
    <div class="toolbar"><label class="search"><span class="sr-only">Cari TTS</span><x-ui.icon name="search" /><input wire:model.live.debounce.300ms="q" type="search" placeholder="Cari nomor, nama, atau HP" maxlength="100"></label><span class="total">{{ $orders->total() }} TTS</span></div>
    <table class="table"><thead><tr><th class="act">Aksi</th><th>Nomor TTS</th><th>Customer</th><th>Barang · keluhan</th><th>Status</th><th>Teknisi</th></tr></thead><tbody>
        @forelse($orders as $order)
            @php($canEdit = $user->role === \App\Enums\UserRole::Admin || ($order->created_by === $user->id && $order->status === \App\Enums\ServiceOrderStatus::Diterima))
            <tr wire:key="tts-{{ $order->id }}"><td class="act">
                @if($canEdit)<x-ui.row-actions :id="'tts-actions-'.$order->id" :label="$order->number"><a role="menuitem" href="{{ route('tts.show', $order->id) }}"><x-ui.icon name="eye" />Lihat TTS</a><a role="menuitem" href="{{ route('tts.edit', $order->id) }}"><x-ui.icon name="pencil" />Ubah TTS</a></x-ui.row-actions>
                @else<a class="btn sm" href="{{ route('tts.show', $order->id) }}">Lihat</a>@endif
            </td><td class="c-id"><a class="id" href="{{ route('tts.show', $order->id) }}">{{ $order->number }}</a></td><td class="c-who">{{ $order->customer_name }}</td><td class="c-what">{{ $order->category->name }}{{ $order->item_description ? ' · '.$order->item_description : '' }}<span class="sub">{{ $order->complaint }}</span></td><td class="c-st"><x-ui.status :status="ucfirst($order->status->value)" /></td><td class="c-meta">{{ $order->technician?->name ?? 'Belum ditetapkan' }}</td></tr>
        @empty
            <tr><td colspan="6"><div class="empty"><strong>{{ $q || $status ? 'Tidak ada TTS yang cocok' : 'Belum ada TTS' }}</strong><span class="small">{{ $q || $status ? 'Coba kata lain, atau pilih Semua.' : 'Buat tanda terima untuk barang yang masuk.' }}</span></div></td></tr>
        @endforelse
    </tbody></table>
    @if($orders->hasPages())<nav class="pagination" aria-label="Halaman daftar TTS"><button class="btn" type="button" wire:click="previousPage" @disabled($orders->onFirstPage())>Sebelumnya</button><span class="small">{{ $orders->currentPage() }} / {{ $orders->lastPage() }}</span><button class="btn" type="button" wire:click="nextPage" @disabled(! $orders->hasMorePages())>Berikutnya</button></nav>@endif
</div>
