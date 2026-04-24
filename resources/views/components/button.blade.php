@props([
    'variant' => 'primary',
    'size' => null,
    'type' => 'button',
    'icon' => null,
    'loading' => false,
])

@php
    $classes = 'btn btn-'.$variant.($size ? ' btn-'.$size : '');
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($loading)
        <span class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
    @endif
    @if($icon)
        <i class="bi bi-{{ $icon }} me-1"></i>
    @endif
    <span class="btn-label">{{ $slot }}</span>
</button>
