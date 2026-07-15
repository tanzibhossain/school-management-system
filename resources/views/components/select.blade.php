{{-- Select Component --}}
@props([
    'name',
    'id' => null,
    'label' => null,
    'options' => [],
    'value' => null,
    'placeholder' => '-- Select --',
    'required' => false,
    'disabled' => false,
    'multiple' => false,
    'error' => null,
    'hint' => null,
    'size' => 'md',
    'class' => '',
    'wrapperClass' => '',
    'labelClass' => '',
    'hintClass' => '',
    'errorClass' => 'text-danger text-xs',
    'autocomplete' => null,
])

@php
    $selectId = $id ?? 'select-' . uniqid();
    $selectClass = 'form-select';
    $wrapperClass = 'form-group';
    $labelClass = 'form-label';
    $hintClass = 'form-hint';
    $errorClass = $errorClass;

    if ($size === 'sm') $selectClass .= ' form-select-sm';
    if ($size === 'lg') $selectClass .= ' form-select-lg';
    if ($class) $selectClass .= ' ' . $class;
    if ($wrapperClass) $wrapperClass .= ' ' . $wrapperClass;
    if ($labelClass) $labelClass .= ' ' . $labelClass;
    if ($hintClass) $hintClass .= ' ' . $hintClass;
    if ($errorClass) $errorClass .= ' ' . $errorClass;
@endphp

<div class="{{ $wrapperClass }}">
    @if($label)
        <label for="{{ $id ?? 'select-' . uniqid() }}" class="{{ $labelClass }}">
            {{ $label }}
            @if($required)
                <span class="text-danger" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    <select
        id="{{ $id ?? 'select-' . uniqid() }}"
        name="{{ $name }}"
        class="{{ $selectClass }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        @if($multiple) multiple @endif
        @if($error) aria-invalid="true" @else aria-invalid="false" @endif
        @if($required) aria-required="true" @endif
        @if($hint || $error) aria-describedby="{{ $id ?? 'select-' . uniqid() }}-help" @endif
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
    >
        @if($placeholder)
            <option value="" {{ is_null($value) ? 'selected' : '' }}>{{ $placeholder }}</option>
        @endif
        @foreach($options as $key => $label)
            <option value="{{ $key }}" {{ (string)$key == (string)$value ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    @if($hint || $error)
        <div id="{{ $id ?? 'select-' . uniqid() }}-help" class="form-hint" role="alert">
            @if($error)
                <span class="{{ $errorClass }}">{{ $error }}</span>
            @else
                {{ $hint }}
            @endif
        </div>
    @endif
</div>