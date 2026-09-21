@props(['color'])

@php
    $hex = preg_match('/^#[0-9a-f]{6}$/i', $color->hex) ? $color->hex : '#ffffff';
@endphp

<span class="inline-flex items-center gap-2">
    <span
        aria-hidden="true"
        class="h-3 w-3 shrink-0 rounded-full border border-gray-300 shadow-sm"
        style="background-color: {{ $hex }}"
    ></span>

    <span>{{ $color->name }}</span>
</span>
