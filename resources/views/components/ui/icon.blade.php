@props(['name'])
<svg {{ $attributes->class(['icon']) }} aria-hidden="true"><use href="/icons/lucide.svg#i-{{ $name }}"></use></svg>
