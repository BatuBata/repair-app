<x-ui.row-actions :id="'tts-detail-actions-'.$placement" :label="$order->number">
    <a role="menuitem" href="{{ route('tts.edit', $order->id) }}"><x-ui.icon name="pencil" />Ubah data TTS</a>
    @if(in_array($order->status->value, ['selesai', 'diambil'], true))<a role="menuitem" href="{{ route('invoice.edit', $order->id) }}"><x-ui.icon name="receipt" />Koreksi nota</a>@endif
    @if(in_array($order->status->value, ['diterima', 'dikerjakan'], true))<button role="menuitem" class="danger" type="button" data-open="cancel-order"><x-ui.icon name="ban" />Batalkan TTS</button>@endif
</x-ui.row-actions>
@if($order->status->value === 'diterima')<button class="btn primary" type="submit" form="assign-technician" wire:loading.attr="disabled" wire:target="assign">Tetapkan teknisi</button>
@elseif($order->status->value === 'dikerjakan')<a class="btn primary" href="{{ route('invoice.edit', $order->id) }}">Isi rincian &amp; nota</a>
@elseif($order->status->value === 'selesai')<button class="btn primary" type="submit" form="collect-order" wire:loading.attr="disabled" wire:target="collect">Tandai Diambil</button>
@elseif($order->status->value === 'diambil')<a class="btn primary" href="{{ route('invoice.edit', $order->id) }}">Koreksi nota</a>@endif
