@props(['id', 'label' => 'baris'])
<button class="btn sm act-btn" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="{{ $id }}" aria-label="Pilihan untuk {{ $label }}"><x-ui.icon name="ellipsis" /></button>
<div class="menu" role="menu" id="{{ $id }}" hidden>{{ $slot }}</div>
