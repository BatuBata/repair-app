@props(['name', 'label', 'type' => 'text', 'placeholder' => null, 'value' => ''])
@php
    $placeholder ??= match ($type) {
        'date', 'datetime-local', 'file' => null,
        'number' => '0',
        'password' => 'Minimal 9 karakter',
        'tel' => '08xxxxxxxxxx',
        default => 'Masukkan '.\Illuminate\Support\Str::lower($label),
    };
@endphp
@if($type === 'date')
    <x-ui.date-picker :$name :$label :$value :min="(string) $attributes->get('min', '1000-01-01')" :max="(string) $attributes->get('max', '9999-12-31')" :required="$attributes->has('required')" :invalid="$attributes->get('aria-invalid') === 'true'" :describedby="$attributes->get('aria-describedby')" {{ $attributes->whereStartsWith('wire:') }} />
@else
    <label class="field"><span>{{ $label }}</span><input class="input" name="{{ $name }}" type="{{ $type }}" @if($placeholder !== null) placeholder="{{ $placeholder }}" @endif {{ $attributes }}></label>
@endif
