{{-- Modal Component --}}
@props([
    'id' => null,
    'title' => null,
    'size' => 'md', // sm, md, lg, xl, fullscreen
    'centered' => false,
    'scrollable' => false,
    'backdrop' => true, // true, false, 'static'
    'keyboard' => true,
    'show' => false,
    'class' => '',
    'headerClass' => '',
    'bodyClass' => '',
    'footerClass' => '',
])

@php
    $modalId = $id ?? 'modal-' . uniqid();
    $modalSizeClasses = [
        'sm' => 'modal-sm',
        'md' => '',
        'lg' => 'modal-lg',
        'xl' => 'modal-xl',
        'fullscreen' => 'modal-fullscreen',
    ];
    $sizeClass = $modalSizeClasses[$size] ?? '';
    $centeredClass = $centered ? 'modal-dialog-centered' : '';
    $scrollableClass = $scrollable ? 'modal-dialog-scrollable' : '';
    $backdropAttr = $backdrop === 'static' ? 'data-bs-backdrop="static"' : ($backdrop ? 'data-bs-backdrop="true"' : 'data-bs-backdrop="false"');
    $keyboardAttr = $keyboard ? 'data-bs-keyboard="true"' : 'data-bs-keyboard="false"';
@endphp

<div
    class="modal fade {{ $class }}"
    id="{{ $modalId }}"
    tabindex="-1"
    aria-labelledby="{{ $modalId }}-label"
    aria-hidden="true"
    {{ $backdropAttr }}
    {{ $keyboardAttr }}
    @if($show) data-bs-show="true" @endif
>
    <div class="modal-dialog {{ $sizeClass }} {{ $centeredClass }} {{ $scrollableClass }}">
        <div class="modal-content">
            @if($title)
            <div class="modal-header {{ $headerClass }}">
                <h5 class="modal-title" id="{{ $modalId }}-label">{{ $title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            @endif
            <div class="modal-body {{ $bodyClass }}">
                {{ $slot }}
            </div>
            @isset($footer)
            <div class="modal-footer {{ $footerClass }}">
                {{ $footer }}
            </div>
            @endisset
        </div>
    </div>
</div>

@if($show)
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('{{ $modalId }}'), {
            backdrop: {{ $backdrop === 'static' ? '"static"' : ($backdrop ? 'true' : 'false') }},
            keyboard: {{ $keyboard ? 'true' : 'false' }}
        });
        modal.show();
    });
</script>
@endpush
@endif