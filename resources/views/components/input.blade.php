@props([
    'type' => 'text',
    'name',
    'id' => null,
    'value' => null,
    'label' => null,
    'placeholder' => null,
    'required' => false,
    'disabled' => false,
    'readonly' => false,
    'error' => null,
    'hint' => null,
    'prefix' => null,
    'suffix' => null,
    'size' => 'md', // sm, md, lg
    'class' => '',
    'wrapperClass' => '',
    'labelClass' => '',
    'inputClass' => '',
    'hintClass' => '',
    'errorClass' => 'text-danger text-xs',
    'min' => null,
    'max' => null,
    'step' => null,
    'minlength' => null,
    'maxlength' => null,
    'pattern' => null,
    'autocomplete' => null,
    'autofocus' => false,
])

@php
    $inputId = $id ?? 'input-' . uniqid();
    $inputClassString = 'form-input';
    $wrapperClassString = 'form-group';
    $labelClassString = 'form-label';
    $hintClassString = 'form-hint';
    $errorClassString = $errorClass;

    if ($size === 'sm') $inputClassString .= ' form-input-sm';
    if ($size === 'lg') $inputClassString .= ' form-input-lg';
    if ($inputClass) $inputClassString .= ' ' . $inputClass;
    if ($wrapperClass) $wrapperClassString .= ' ' . $wrapperClass;
    if ($labelClass) $labelClassString .= ' ' . $labelClass;
    if ($hintClass) $hintClassString .= ' ' . $hintClass;
    if ($errorClass) $errorClassString .= ' ' . $errorClass;

    $inputAttributes = [
        'type' => $type,
        'id' => $inputId,
        'name' => $name,
        'value' => $value,
        'placeholder' => $placeholder,
        'required' => $required ? 'required' : null,
        'disabled' => $disabled ? 'disabled' : null,
        'readonly' => $readonly ? 'readonly' : null,
        'min' => $min,
        'max' => $max,
        'step' => $step,
        'minlength' => $minlength,
        'maxlength' => $maxlength,
        'pattern' => $pattern,
        'autocomplete' => $autocomplete,
        'autofocus' => $autofocus ? 'autofocus' : null,
        'aria-invalid' => $error ? 'true' : 'false',
        'aria-required' => $required ? 'true' : 'false',
        'aria-describedby' => ($hint || $error) ? $inputId . '-help' : null,
        'class' => $inputClassString,
    ];

    // Remove null attributes
    $inputAttributes = array_filter($inputAttributes, fn($v) => $v !== null);
    $inputAttrString = '';
    foreach ($inputAttributes as $key => $value) {
        $inputAttrString .= " $key=\"" . e($value) . "\"";
    }
@endphp

<div class="{{ $wrapperClassString }}">
    @if($label)
        <label for="{{ $inputId }}" class="{{ $labelClassString }}">
            {{ $label }}
            @if($required)
                <span class="text-danger" aria-hidden="true">*</span>
            @endif
        </label>
    @endif

    @if($prefix || $suffix)
        <div class="input-group">
            @if($prefix)
                <span class="input-group-text">{{ $prefix }}</span>
            @endif
            <input {{ $inputAttrString }}>
            @if($suffix)
                <span class="input-group-text">{{ $suffix }}</span>
            @endif
        </div>
    @else
        <input {{ $inputAttrString }}>
    @endif

    @if($hint || $error)
        <div id="{{ $inputId }}-help" class="{{ $hintClassString }}" role="alert">
            @if($error)
                <span class="{{ $errorClassString }}">{{ $error }}</span>
            @else
                {{ $hint }}
            @endif
        </div>
    @endif
</div>