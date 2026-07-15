{{-- Alert Component --}}
@props([
    'variant' => 'info', // success, warning, danger, info
    'dismissible' => true,
    'icon' => true,
    'title' => null,
    'class' => '',
])

@php
    $variants = [
        'success' => 'alert-success',
        'warning' => 'alert-warning',
        'danger' => 'alert-danger',
        'info' => 'alert-info',
    ];

    $variantClass = $variants[$variant] ?? $variants['info'];
    $iconMap = [
        'success' => 'bi-check-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'danger' => 'bi-exclamation-triangle-fill',
        'info' => 'bi-info-circle-fill',
    ];
    $iconClass = $iconMap[$variant] ?? $iconMap['info'];
@endphp

<div class="alert {{ $variantClass }} alert-dismissible fade show {{ $class }}" role="alert" {{ $attributes }}>
    @if($icon)
    <i class="bi {{ $iconClass }} alert-icon me-2" aria-hidden="true"></i>
    @endif

    <div class="alert-content">
        @if($title)
        <div class="alert-title fw-semibold mb-1">{{ $title }}</div>
        @endif
        {{ $slot }}
    </div>

    @if($dismissible)
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>