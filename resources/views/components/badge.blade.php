{{-- Badge Component --}}
@props([
    'variant' => 'neutral', // primary, success, warning, danger, neutral
    'dot' => false,
    'dotColor' => null,
    'size' => 'md', // sm, md, lg
    'class' => '',
    'attributes' => [],
])

@php
    $badgeClass = 'badge';
    $variantClass = 'badge-' . $variant;
    $sizeClass = 'badge-' . $size;

    if ($dot) {
        $badgeClass .= ' badge-dot';
    }

    if ($class) $badgeClass .= ' ' . $class;

    $attrString = '';
    foreach ($attributes as $key => $value) {
        if ($value !== false && $value !== null) {
            $attrString .= " $key=\"" . e($value) . "\"";
        }
    }

    $dotStyle = '';
    if ($dot && $dotColor) {
        $dotStyle = 'style="background-color: ' . e($dotColor) . ';"';
    } elseif ($dot) {
        $dotStyle = 'style="background-color: currentColor;"';
    }
@endphp

<span class="{{ $badgeClass }} {{ $variantClass }} {{ $sizeClass }}" {{ $attrString }}>
    @if($dot)
        <span class="badge-dot-indicator" {{ $dotStyle }}></span>
    @endif
    {{ $slot }}
</span>