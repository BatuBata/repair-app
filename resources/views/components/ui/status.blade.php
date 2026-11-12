@props(['status'])
<span {{ $attributes->class(['pill', 'st-'.strtolower($status)]) }}>{{ $status }}</span>
