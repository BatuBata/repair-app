@unless($primaryOnly ?? false)
<x-ui.row-actions :id="'tts-detail-actions-'.$placement" :label="$order->number">
    <a role="menuitem" href="{{ route('tts.edit', $order->id) }}"><x-ui.icon name="pencil" />Ubah data TTS</a>
    @if(in_array($order->status->value, ['selesai', 'diambil'], true))<a role="menuitem" href="{{ route('invoice.edit', $order->id) }}"><x-ui.icon name="receipt" />Koreksi nota</a>@endif
    @if($financial && $financial['price'] > 0 && in_array($order->status->value, ['selesai', 'diambil'], true))<button role="menuitem" type="button" data-open="payment-order"><x-ui.icon name="circle-check" />{{ $order->paid_at ? 'Ubah pembayaran' : 'Catat pembayaran' }}</button>@endif
    @include('livewire.tts.document-actions')
    @if(in_array($order->status->value, ['diterima', 'pengecekan', 'dikerjakan'], true))<button role="menuitem" class="danger" type="button" data-open="cancel-order"><x-ui.icon name="ban" />Batalkan TTS</button>@endif
</x-ui.row-actions>
@endunless
@unless($menuOnly ?? false)
@if($order->status->value === 'diterima')<button class="btn primary" type="submit" form="assign-technician" wire:loading.attr="disabled" wire:target="assign">Tetapkan teknisi</button>
@elseif($order->status->value === 'pengecekan')<button class="btn primary" type="button" wire:click="sendInspection" wire:loading.attr="disabled" wire:target="sendInspection,saveInspection,startWork"><span wire:loading.remove wire:target="sendInspection">Simpan &amp; buka WhatsApp</span><span wire:loading wire:target="sendInspection">Menyiapkan WA…</span></button>
@elseif($order->status->value === 'dikerjakan')<a class="btn primary" href="{{ route('invoice.edit', $order->id) }}">Buat nota</a>
@elseif($order->status->value === 'selesai')
    @if($needsPayment)<button class="btn primary" type="button" data-open="payment-order">Catat pembayaran</button>
    @else<button class="btn primary" type="submit" form="collect-order" wire:loading.attr="disabled" wire:target="collect">Tandai Diambil</button>@endif
@endif

@endunless
