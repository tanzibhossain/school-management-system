{{-- Form Layout Component --}}
@props([
    'action' => null,
    'method' => 'POST',
    'enctype' => null,
    'class' => '',
    'novalidate' => false,
    'layout' => 'vertical', // vertical, horizontal, inline
    'labelCol' => 'col-md-3',
    'inputCol' => 'col-md-9',
    'nocsrf' => false,
])

@php
    $formClasses = ['form', 'form-' . $layout];
    if ($class) $formClasses[] = $class;
    $formClassString = implode(' ', $formClasses);
@endphp

<form
    {{ $action ? 'action="' . e($action) . '"' : '' }}
    method="{{ $method }}"
    @if($enctype) enctype="{{ $enctype }}" @endif
    @if($novalidate) novalidate @endif
    class="{{ $formClassString }}"
    {{ $attributes }}
>
    @unless($nocsrf)
        @csrf
    @endunless

    @if($method !== 'GET' && $method !== 'POST')
        <input type="hidden" name="_method" value="{{ strtoupper($method) }}">
    @endif

    <div class="form-body">
        {{ $slot }}
    </div>

    @isset($actions)
    <div class="form-actions d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-border">
        {{ $actions }}
    </div>
    @endisset
</form>