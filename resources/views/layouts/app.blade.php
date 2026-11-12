@props(['title' => null, 'navigationPath' => null])
<x-layouts::app.sidebar :title="$title ?? null" :navigation-path="$navigationPath ?? null">
    {{ $slot }}
</x-layouts::app.sidebar>
