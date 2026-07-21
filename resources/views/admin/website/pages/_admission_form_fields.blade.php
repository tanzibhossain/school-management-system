{{-- Admission form field configuration UI. Vars: $prefix, $data --}}
@php
    $fields = $data['fields'] ?? [];
    $customFields = [];
    $standardKeys = ['last_name', 'blood_group', 'student_phone', 'photo', 'guardian', 'permanent_address', 'notes'];
    foreach ($fields as $key => $cfg) {
        if (! in_array($key, $standardKeys, true)) {
            $customFields[$key] = $cfg;
        }
    }
    $fieldLabels = [
        'last_name'         => 'Last name',
        'blood_group'       => 'Blood group',
        'student_phone'     => 'Student phone',
        'photo'             => 'Student photo',
        'guardian'          => 'Guardian information',
        'permanent_address' => 'Permanent address',
        'notes'             => 'Notes',
    ];
@endphp

<input type="hidden" name="{{ $prefix }}[type]" value="admission_form">

{{-- Heading & Intro --}}
<div class="mb-2">
    <label class="form-label small text-muted mb-1">{{ __('Form heading') }}</label>
    <input type="text" name="{{ $prefix }}[data][heading]" class="form-control form-control-sm"
           value="{{ $data['heading'] ?? '' }}" placeholder="{{ __('Online Admission') }}">
</div>
<div class="mb-3">
    <label class="form-label small text-muted mb-1">{{ __('Intro text') }}</label>
    <textarea name="{{ $prefix }}[data][intro]" rows="2" class="form-control form-control-sm"
              placeholder="{{ __('Optional introduction text...') }}">{{ $data['intro'] ?? '' }}</textarea>
</div>

<hr class="my-2">

{{-- Standard optional fields --}}
<div class="mb-3">
    <h6 class="fw-semibold small text-uppercase text-muted mb-2">{{ __('Optional fields') }}</h6>
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="w-auto">{{ __('Field') }}</th>
                    <th class="w-auto">{{ __('Enabled') }}</th>
                    <th>{{ __('Label override') }}</th>
                    <th class="w-auto">{{ __('Required') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($standardKeys as $key)
                    @php
                        $cfg = $fields[$key] ?? ['enabled' => true, 'label' => $fieldLabels[$key], 'required' => false];
                        $nameBase = $prefix . '[data][fields][' . $key . ']';
                    @endphp
                    <tr>
                        <td class="fw-semibold small">{{ $fieldLabels[$key] }}</td>
                        <td>
                            <div class="form-check form-switch">
                                <input type="hidden" name="{{ $nameBase }}[enabled]" value="0">
                                <input class="form-check-input" type="checkbox" name="{{ $nameBase }}[enabled]"
                                       value="1" @checked($cfg['enabled']) role="switch">
                            </div>
                        </td>
                        <td>
                            <input type="text" name="{{ $nameBase }}[label]" class="form-control form-control-sm"
                                   value="{{ $cfg['label'] }}" placeholder="{{ $fieldLabels[$key] }}">
                        </td>
                        <td>
                            <div class="form-check form-switch">
                                <input type="hidden" name="{{ $nameBase }}[required]" value="0">
                                <input class="form-check-input" type="checkbox" name="{{ $nameBase }}[required]"
                                       value="1" @checked($cfg['required']) role="switch">
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<hr class="my-2">

{{-- Custom fields --}}
<div class="mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-semibold small text-uppercase text-muted mb-0">{{ __('Custom fields') }}</h6>
        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCustomField('{{ $prefix }}')">
            <i class="bi bi-plus-lg"></i> Add field
        </button>
    </div>
    <div id="{{ str_replace(['[', ']'], ['_', '_'], $prefix) }}_custom_fields">
        @foreach ($customFields as $key => $cfg)
            @include('admin.website.pages._custom_field_row', ['prefix' => $prefix, 'key' => $key, 'cfg' => $cfg])
        @endforeach
    </div>
</div>

{{-- Custom field template (hidden, cloned by JS) --}}
<template id="{{ str_replace(['[', ']'], ['_', '_'], $prefix) }}_custom_field_tpl">
    @php
        $tplPrefix = $prefix . '[data][fields][__KEY__]';
    @endphp
    <div class="custom-field-row row g-2 align-items-end mb-2 p-2 border rounded bg-light">
        <div class="col-md-4">
            <label class="form-label small text-muted mb-1">{{ __('Label') }} <span class="text-danger">*</span></label>
            <input type="text" name="{{ $tplPrefix }}[label]" class="form-control form-control-sm"
                   placeholder="{{ __('Emergency contact') }}" required>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted mb-1">{{ __('Type') }}</label>
            <select name="{{ $tplPrefix }}[type]" class="form-select form-select-sm field-type-select">
                <option value="text">{{ __('Text') }}</option>
                <option value="textarea">{{ __('Textarea') }}</option>
                <option value="select">{{ __('Select') }}</option>
                <option value="number">{{ __('Number') }}</option>
                <option value="date">{{ __('Date') }}</option>
                <option value="file">{{ __('File upload') }}</option>
                <option value="checkbox">{{ __('Checkbox') }}</option>
            </select>
        </div>
        <div class="col-md-2 field-options-container" style="display:none;">
            <label class="form-label small text-muted mb-1">{{ __('Options (for select/checkbox)') }}</label>
            <input type="text" name="{{ $tplPrefix }}[options]" class="form-control form-control-sm"
                   placeholder="{{ __('Option 1,Option 2') }}">
        </div>
        <div class="col-md-1">
            <div class="form-check form-switch">
                <input type="hidden" name="{{ $tplPrefix }}[enabled]" value="0">
                <input class="form-check-input" type="checkbox" name="{{ $tplPrefix }}[enabled]"
                       value="1" role="switch" checked>
                <label class="form-check-label small">{{ __('Enabled') }}</label>
            </div>
        </div>
        <div class="col-md-1">
            <div class="form-check form-switch">
                <input type="hidden" name="{{ $tplPrefix }}[required]" value="0">
                <input class="form-check-input" type="checkbox" name="{{ $tplPrefix }}[required]"
                       value="1" role="switch">
                <label class="form-check-label small">{{ __('Required') }}</label>
            </div>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeCustomField(this)"
                    title="{{ __('Remove') }}"><i class="bi bi-trash"></i></button>
        </div>
    </div>
</template>

@push('scripts')
<script>
    var customFieldCounter = 1000;

    function addCustomField(prefix) {
        console.log('addCustomField called with prefix:', prefix);
        var tplId = prefix.replace(/[\[\]]/g, '_') + '_custom_field_tpl';
        var containerId = prefix.replace(/[\[\]]/g, '_') + '_custom_fields';
        console.log('Looking for template:', tplId, 'container:', containerId);

        var tpl = document.getElementById(tplId);
        var container = document.getElementById(containerId);

        if (!tpl) {
            console.error('Template not found:', tplId);
            return;
        }
        if (!container) {
            console.error('Container not found:', containerId);
            return;
        }

        var html = tpl.innerHTML.replace(/__KEY__/g, 'custom_' + customFieldCounter++);
        container.insertAdjacentHTML('beforeend', html);
        console.log('Custom field added successfully');
    }

    function removeCustomField(btn) {
        btn.closest('.custom-field-row').remove();
    }

    // Show/hide options field based on type (select or checkbox)
    document.addEventListener('change', function(e) {
        if (e.target.matches('select[name$="[type]"]')) {
            var row = e.target.closest('.custom-field-row');
            var optionsContainer = row?.querySelector('.field-options-container');
            if (optionsContainer) {
                var showOptions = e.target.value === 'select' || e.target.value === 'checkbox';
                optionsContainer.style.display = showOptions ? '' : 'none';
            }
        }
    });

    // Initialize options visibility on load
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('select[name$="[type]"]').forEach(function(select) {
            var row = select.closest('.custom-field-row');
            var optionsContainer = row?.querySelector('.field-options-container');
            if (optionsContainer) {
                var showOptions = select.value === 'select' || select.value === 'checkbox';
                optionsContainer.style.display = showOptions ? '' : 'none';
            }
        });
    });
</script>
@endpush