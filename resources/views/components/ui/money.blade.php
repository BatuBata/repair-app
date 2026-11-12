@props(['name', 'label', 'value' => ''])
<label class="field"><span>{{ $label }}</span><input class="input" type="text" inputmode="numeric" data-money="money-{{ $name }}" value="{{ $value === '' ? '' : number_format((int) $value, 0, ',', '.') }}" {{ $attributes }}><input type="hidden" name="{{ $name }}" id="money-{{ $name }}" value="{{ $value }}"></label>
