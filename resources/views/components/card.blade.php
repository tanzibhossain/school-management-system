{{-- Card Component --}}
@props([
    'header' => null,
    'footer' => null,
    'variant' => 'default', // default, outlined, elevated, ghost
    'padding' => 'md', // none, sm, md, lg
    'clickable' => false,
    'href' => null,
    'class' => '',
    'headerClass' => '',
    'bodyClass' => '',
    'footerClass' => '',
])

@php
    $variants = [
        'default' => '',
        'outlined' => 'border border-border',
        'elevated' => 'shadow-md',
        'ghost' => 'bg-transparent',
    ];

    $paddingClasses = [
        'none' => 'p-0',
        'sm' => 'p-3',
        'md' => 'p-4',
        'lg' => 'p-6',
    ];

    $variantClass = $variants[$variant] ?? '';
    $paddingClass = $paddingClasses[$padding] ?? $paddingClasses['md'];

    $classes = ['card', $variantClass, $paddingClass];
    if ($class) $classes[] = $class;
    if ($clickable) $classes[] = 'card-clickable';

    $classString = implode(' ', $classes);

    $isLink = !is_null($href);
    $tag = $clickable || $href ? 'a' : 'div';
    $hrefAttr = $href ? 'href="{{ $href }}"' : '';
@endphp

<{{ $tag }} class="{{ $classString }}" {{ $hrefAttr }} {{ $attributes }}>
    @if($header)
    <div class="card-header {{ $headerClass }}">
        {{ $header }}
    </div>
    @endif

    <div class="card-body {{ $bodyClass }}">
        {{ $slot }}
    </div>

    @if($footer)
    <div class="card-footer {{ $footerClass }}">
        {{ $footer }}
    </div>
    @endif
</{{ $tag }}>