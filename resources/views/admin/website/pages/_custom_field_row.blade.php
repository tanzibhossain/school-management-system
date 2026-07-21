@php
    $tplPrefix = $prefix . '[data][fields][' . $key . ']';
    $type = $cfg['type'] ?? 'text';
    $showOptions = in_array($type, ['select', 'checkbox']);
@endphp
<div class="custom-field-row row g-2 align-items-end mb-2 p-2 border rounded bg-light">
    <div class="col-md-3">
        <label class="form-label small text-muted mb-1">{{ __('Label') }} <span class="text-danger">*</span></label>
        <input type="text" name="{{ $tplPrefix }}[label]" class="form-control form-control-sm"
               value="{{ $cfg['label'] ?? $key }}" required>
    </div>
    <div class="col-md-2">
        <label class="form-label small text-muted mb-1">{{ __('Type') }}</label>
        <select name="{{ $tplPrefix }}[type]" class="form-select form-select-sm field-type-select">
            @foreach(['text'=>'Text','textarea'=>'Textarea','select'=>'Select','number'=>'Number','date'=>'Date','file'=>'File upload','checkbox'=>'Checkbox'] as $t => $l)
                <option value="{{ $t }}" @selected(($cfg['type'] ?? 'text') === $t)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 field-options-container" style="{{ $showOptions ? '' : 'display:none' }}">
        <label class="form-label small text-muted mb-1">{{ __('Options (for select/checkbox)') }}</label>
        <input type="text" name="{{ $tplPrefix }}[options]" class="form-control form-control-sm"
               value="{{ is_array($cfg['options'] ?? null) ? implode(',', $cfg['options']) : ($cfg['options'] ?? '') }}"
               placeholder="{{ __('Option 1,Option 2') }}">
    </div>
    <div class="col-md-1">
        <div class="form-check form-switch">
            <input type="hidden" name="{{ $tplPrefix }}[enabled]" value="0">
            <input class="form-check-input" type="checkbox" name="{{ $tplPrefix }}[enabled]"
                   value="1" @checked($cfg['enabled'] ?? true) role="switch">
            <label class="form-check-label small">{{ __('Enabled') }}</label>
        </div>
    </div>
    <div class="col-md-1">
        <div class="form-check form-switch">
            <input type="hidden" name="{{ $tplPrefix }}[required]" value="0">
            <input class="form-check-input" type="checkbox" name="{{ $tplPrefix }}[required]"
                   value="1" @checked($cfg['required'] ?? false) role="switch">
            <label class="form-check-label small">{{ __('Required') }}</label>
        </div>
    </div>
    <div class="col-auto">
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeCustomField(this)"
                title="{{ __('Remove') }}"><i class="bi bi-trash"></i></button>
    </div>
</div>