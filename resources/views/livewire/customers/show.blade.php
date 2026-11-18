<div>
    <a class="crumb" href="{{ route('customers.index') }}"><x-ui.icon name="chevron-left" />Pelanggan</a>
    <div class="title-row"><div><h1>{{ $customer->name }}</h1><p class="sub">{{ $customer->phone }}</p></div></div>
    <p>{{ $customer->address ?: 'Alamat belum diisi.' }}</p>
    <section style="margin-top:24px" aria-labelledby="customer-history-title">
        <div class="section-head"><h2 id="customer-history-title">Riwayat servis</h2><span class="meta">{{ $orders->total() }} TTS</span></div>
        <p class="small muted" style="margin-bottom:16px">Riwayat di toko ini berdasarkan nomor HP {{ $customer->phone }}, termasuk TTS lama. Jika nomor HP diubah, riwayat mengikuti nomor yang baru.</p>
        <table class="table"><thead><tr><th class="act">Aksi</th><th>Nomor / tanggal masuk</th><th>Barang / keluhan</th><th>Status</th></tr></thead><tbody>
            @forelse($orders as $order)
                <tr wire:key="customer-order-{{ $order->id }}">
                    <td class="act"><a class="btn" href="{{ route('tts.show', $order->id) }}" aria-label="Lihat {{ $order->number }}">Lihat TTS</a></td>
                    <td class="c-who"><strong>{{ $order->number }}</strong><div class="small muted">{{ $order->created_at->timezone('Asia/Makassar')->format('d/m/Y') }}</div></td>
                    <td class="c-meta">{{ $order->category?->name ?: '—' }}{{ $order->item_description ? ' · '.$order->item_description : '' }}<div class="small muted">{{ $order->complaint }}</div></td>
                    <td class="c-st"><x-ui.status :status="$order->status->value" /></td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="empty">Belum ada riwayat servis dengan nomor HP ini.</div></td></tr>
            @endforelse
        </tbody></table>
        {{ $orders->links() }}
    </section>
</div>
