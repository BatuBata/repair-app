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
    <div class="notice-content"><span class="sr-only">{{ $title }}: </span><div class="notice-message">{{ $slot }}</div></div>
    <button class="notice-close" type="button" data-dismiss-notice aria-label="Tutup pesan"><x-ui.icon name="x" /></button>
</div>
