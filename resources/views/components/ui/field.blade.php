@props(['name', 'label', 'type' => 'text'])
<label class="field"><span>{{ $label }}</span><input class="input" name="{{ $name }}" type="{{ $type }}" {{ $attributes }}></label>
