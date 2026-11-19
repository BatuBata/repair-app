@props(['label', 'placeholder', 'total' => null, 'unit' => ''])
<div class="toolbar index-toolbar">
    <label class="search"><span class="sr-only">{{ $label }}</span><x-ui.icon name="search" /><input type="search" maxlength="100" placeholder="{{ $placeholder }}" {{ $attributes }}></label>
    @isset($total)<span class="total">{{ number_format($total, 0, ',', '.') }} {{ $unit }}</span>@endisset
</div>
