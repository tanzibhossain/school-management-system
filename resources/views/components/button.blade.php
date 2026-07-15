{{-- Button Component --}}
@props([
    'type' => 'button',
    'variant' => 'primary', // primary, secondary, ghost, danger, outline, link
    'size' => 'md', // sm, md, lg
    'disabled' => false,
    'loading' => false,
    'fullWidth' => false,
    'class' => '',
    'id' => null,
    'icon' => null,
    'iconPosition' => 'left', // left, right
    'attributes' => [],
])

@php
    $buttonClass = 'btn';
    $buttonId = $id ?? 'btn-' . uniqid();

    // Variant
    $variantClass = 'btn-' . $variant;
    $buttonClass .= ' ' . $variantClass;

    // Size
    $sizeClass = 'btn-' . $size;
    $buttonClass .= ' ' . $sizeClass;

    if ($fullWidth) $buttonClass .= ' w-100';
    if ($class) $buttonClass .= ' ' . $class;

    $attrString = '';
    foreach ($attributes as $key => $value) {
        if ($value !== false && $value !== null) {
            $attrString .= " $key=\"" . e($value) . "\"";
        }
    }
@endphp

<button
    type="{{ $type }}"
    id="{{ $buttonId }}"
    class="{{ $buttonClass }}"
    @if($disabled) disabled @endif
    @if($loading) aria-busy="true" @endif
    {{ $attrString }}
>
    @if($loading)
        <span class="spinner spinner-sm me-2" aria-hidden="true"></span>
        <span class="visually-hidden">Loading...</span>
    @elseif($icon && $iconPosition === 'left')
        <i class="{{ $icon }} me-2" aria-hidden="true"></i>
    @endif

    {{ $slot }}

    @if($icon && $iconPosition === 'right')
        <i class="{{ $icon }} ms-2" aria-hidden="true"></i>
    @endif
</button>