@props(['label', 'placeholder', 'total', 'unit'])
<div class="toolbar index-toolbar">
    <label class="search"><span class="sr-only">{{ $label }}</span><x-ui.icon name="search" /><input type="search" maxlength="100" placeholder="{{ $placeholder }}" {{ $attributes }}></label>
    <span class="total">{{ number_format($total, 0, ',', '.') }} {{ $unit }}</span>
</div>
