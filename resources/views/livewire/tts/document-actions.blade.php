<a role="menuitem" href="{{ route('tts.print', $order->id) }}"><x-ui.icon name="printer" />Cetak struk</a>
<a role="menuitem" href="{{ route('tts.receipt.pdf', $order->id) }}"><x-ui.icon name="download" />PDF struk</a>
@if($isAdmin)<a role="menuitem" href="{{ route('tts.pdf', $order->id) }}"><x-ui.icon name="file-text" />PDF A5</a>@endif
