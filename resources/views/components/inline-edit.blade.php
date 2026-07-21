{{-- Inline Edit Component --}}
@props([
    'entity' => null,
    'field' => null,
    'value' => null,
    'type' => 'text', // text, textarea, select, number, date, select2, date-range
    'options' => [],
    'placeholder' => null,
    'validation' => [],
    'url' => null,
    'method' => 'PUT',
    'saveOnBlur' => true,
    'saveOnEnter' => true,
    'cancelOnEscape' => true,
    'placeholder' => 'Click to edit',
    'class' => '',
    'attributes' => [],
])

@php
    $editId = 'inline-edit-' . uniqid();
    $isEditing = false;
    $inputId = $editId . '-input';
    $displayValue = $value ?? $placeholder ?? '';
    $inputType = $type === 'textarea' ? 'textarea' : ($type === 'select' ? 'select' : 'input');
    $validTypes = ['text', 'textarea', 'select', 'number', 'date', 'select2', 'email', 'tel', 'url'];
    $inputType = in_array($type, $validTypes) ? $type : 'text';

    $validationRules = collect($validation)->mapWithKeys(fn($rule, $key) => [$key => $rule])->toJson();
    $optionsJson = collect($options)->toJson();
@endphp

<div id="{{ $editId }}" class="inline-edit {{ $class }}" {{ $attributes }}>
    {{-- Display Mode --}}
    <div class="inline-edit-display" {{ !$value && $placeholder ? 'style="color: var(--color-text-muted);"' : '' }}>
        {{ $displayValue }}
        @if(!$value && $placeholder)
            <span class="text-muted small ms-1">({{ $placeholder }})</span>
        @endif
        <span class="inline-edit-trigger ms-1" title="{{ __('Click To Edit') }}" aria-label="Edit {{ $field }}">
            <i class="bi bi-pencil text-muted"></i>
        </span>
    </div>

    {{-- Edit Mode --}}
    <div class="inline-edit-form d-none" style="min-width: 200px;">
        @if($inputType === 'textarea')
            <textarea
                id="{{ $inputId }}"
                class="form-control form-control-sm"
                rows="3"
                placeholder="{{ $placeholder }}"
                @if(!empty($validation['required'])) required @endif
                data-validation='@json($validation)'
            >{{ $value }}</textarea>
        @elseif($type === 'select')
            <select
                id="{{ $inputId }}"
                class="form-select form-select-sm"
                data-options='{{ $optionsJson }}'
                @if(!empty($validation['required'])) required @endif
                data-validation='@json($validation)'
            >
                @if($placeholder)
                    <option value="">{{ $placeholder }}</option>
                @endif
                @foreach($options as $key => $label)
                    <option value="{{ $key }}" {{ (string)$key === (string)$value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        @else
            <input
                type="{{ $inputType }}"
                id="{{ $inputId }}"
                class="form-control form-control-sm"
                value="{{ $value }}"
                placeholder="{{ $placeholder }}"
                @if(!empty($validation['required'])) required @endif
                @if(isset($validation['min'])) min="{{ $validation['min'] }}" @endif
                @if(isset($validation['max'])) max="{{ $validation['max'] }}" @endif
                @if(isset($validation['step'])) step="{{ $validation['step'] }}" @endif
                @if(isset($validation['pattern'])) pattern="{{ $validation['pattern'] }}" @endif
                @if(isset($validation['minlength'])) minlength="{{ $validation['minlength'] }}" @endif
                @if(isset($validation['maxlength'])) maxlength="{{ $validation['maxlength'] }}" @endif
                data-validation='@json($validation)'
            >
        @endif

        <div class="inline-edit-actions d-flex gap-1 mt-2">
            <button type="button" class="btn btn-sm btn-primary inline-save" title="{{ __('Save (Enter)') }}">
                <i class="bi bi-check-lg"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary inline-cancel" title="{{ __('Cancel (Escape)') }}">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const editContainer = document.getElementById('{{ $editId }}');
    if (!editContainer) return;

    const display = editContainer.querySelector('.inline-edit-display');
    const form = editContainer.querySelector('.inline-edit-form');
    const input = editContainer.querySelector('#{{ $inputId }}');
    const trigger = editContainer.querySelector('.inline-edit-trigger');
    const saveBtn = editContainer.querySelector('.inline-save');
    const cancelBtn = editContainer.querySelector('.inline-cancel');
    const url = '{{ $url }}';
    const method = '{{ $method }}';
    const field = '{{ $field }}';
    const saveOnBlur = {{ $saveOnBlur ? 'true' : 'false' }};
    const saveOnEnter = {{ $saveOnEnter ? 'true' : 'false' }};
    const cancelOnEscape = {{ $cancelOnEscape ? 'true' : 'false' }};
    const validationRules = {{ $validationRules }};

    let originalValue = '';

    function enterEditMode() {
        display.style.display = 'none';
        form.style.display = 'block';
        input.focus();
        input.select();
        originalValue = input.value;

        // For select, convert to select2 if available
        if (input.tagName === 'SELECT' && typeof $().select2 === 'function') {
            $(input).select2({
                dropdownParent: form,
                width: '100%',
                placeholder: '{{ $placeholder }}',
                allowClear: true
            });
        }
    }

    function exitEditMode(save = false) {
        if (save) {
            saveValue();
        } else {
            input.value = originalValue;
            display.style.display = '';
            form.style.display = 'none';
            editContainer.classList.remove('editing');
        }
    }

    function validateInput() {
        const val = input.value;
        const rules = validationRules;

        if (rules.required && !val.trim()) {
            showError('This field is required');
            return false;
        }

        if (rules.minlength && val.length < rules.minlength) {
            showError('Minimum length is ' + rules.minlength);
            return false;
        }

        if (rules.maxlength && val.length > rules.maxlength) {
            showError('Maximum length is ' + rules.maxlength);
            return false;
        }

        if (rules.pattern && !new RegExp(rules.pattern).test(val)) {
            showError('Invalid format');
            return false;
        }

        if (rules.min !== undefined && parseFloat(val) < rules.min) {
            showError('Value must be at least ' + rules.min);
            return false;
        }

        if (rules.max !== undefined && parseFloat(val) > rules.max) {
            showError('Value must be at most ' + rules.max);
            return false;
        }

        if (rules.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
            showError('Invalid email format');
            return false;
        }

        return true;
    }

    function showError(message) {
        // Remove existing error
        const existing = form.querySelector('.inline-error');
        if (existing) existing.remove();

        const error = document.createElement('div');
        error.className = 'inline-error text-danger text-xs mt-1';
        error.textContent = message;
        form.appendChild(error);
        input.classList.add('is-invalid');

        setTimeout(() => {
            error.remove();
            input.classList.remove('is-invalid');
        }, 3000);
    }

    async function saveValue() {
        if (!validateInput()) return;

        const val = input.value;
        const payload = { [field]: val };

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

        try {
            const response = await fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (response.ok) {
                const data = await response.json();
                // Update display with new value
                display.textContent = data[field] ?? val;
                if (!data[field] && val) {
                    display.textContent = val;
                }
                exitEditMode(true);
            } else {
                const error = await response.json();
                showError(error.message || 'Failed to save');
            }
        } catch (err) {
            console.error(err);
            showError('Failed to save. Please try again.');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="bi bi-check-lg"></i>';
        }
    }

    // Event listeners
    display?.addEventListener('click', enterEditMode);
    trigger?.addEventListener('click', (e) => {
        e.stopPropagation();
        enterEditMode();
    });

    saveBtn?.addEventListener('click', saveValue);

    cancelBtn?.addEventListener('click', () => exitEditMode(false));

    input?.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && saveOnEnter && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            saveValue();
        }
        if (e.key === 'Escape' && cancelOnEscape) {
            exitEditMode(false);
        }
    });

    input?.addEventListener('blur', function(e) {
        if (saveOnBlur && !form.contains(e.relatedTarget)) {
            saveValue();
        }
    });

    // Click outside to cancel
    document.addEventListener('click', function(e) {
        if (form.style.display === 'block' && !form.contains(e.target) && !display.contains(e.target)) {
            exitEditMode(false);
        }
    });
})();
</script>
@endpush