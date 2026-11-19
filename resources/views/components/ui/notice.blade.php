@props(['type' => 'info'])
@php
    [$variant, $icon, $title, $role] = match ($type) {
        'success' => ['success', 'circle-check', 'Berhasil', 'status'],
        'error' => ['error', 'triangle-alert', 'Periksa kembali', 'alert'],
        'warning' => ['warning', 'triangle-alert', 'Perhatian', 'status'],
        default => ['info', 'info', 'Informasi', 'status'],
    };
@endphp
<div {{ $attributes->class(['notice', 'notice-'.$variant])->merge(['role' => $role, 'aria-atomic' => 'true']) }}>
    <x-ui.icon :name="$icon" />
    <div class="notice-content"><strong class="notice-title">{{ $title }}</strong><div class="notice-message">{{ $slot }}</div></div>
</div>
