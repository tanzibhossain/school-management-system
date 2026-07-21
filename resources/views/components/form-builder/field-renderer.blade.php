{{-- Field Renderer for Form Builder Canvas --}}
@props(['field', 'index'])

@php
    $field = $field ?? [];
    $index = $index ?? 0;
    $type = $field['type'] ?? 'text';
    $label = $field['label'] ?? 'Field';
    $required = $field['required'] ?? false;
    $placeholder = $field['placeholder'] ?? '';
@endphp

<div class="form-field-wrapper mb-3 p-3 border rounded bg-white position-relative"
     data-index="{{ $index }}"
     data-field-type="{{ $type }}"
     draggable="true">

    {{-- Drag Handle --}}
    <div class="field-drag-handle position-absolute top-0 end-0 m-2 text-muted" style="cursor: grab;">
        <i class="bi bi-grip-vertical"></i>
    </div>

    {{-- Toolbar --}}
    <div class="field-toolbar position-absolute top-0 end-0 m-2 d-none gap-1">
        <button type="button" class="btn btn-ghost btn-sm edit-field" data-index="{{ $index }}" title="{{ __('Edit') }}">
            <i class="bi bi-pencil"></i>
        </button>
        <button type="button" class="btn btn-ghost btn-sm text-danger delete-field" data-index="{{ $index }}" title="{{ __('Delete') }}">
            <i class="bi bi-trash"></i>
        </button>
    </div>

    {{-- Field Content --}}
    <div class="field-content">
        <div class="field-header d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-slate-100 text-slate-600 field-type-badge">{{ $type }}</span>
                <strong>{{ $label }}</strong>
                @if($required)
                    <span class="badge bg-danger">{{ __('Required') }}</span>
                @endif
            </div>
            <div class="field-actions">
                <button type="button" class="btn btn-ghost btn-sm edit-field" data-index="{{ $index }}" title="{{ __('Edit') }}">
                    <i class="bi bi-pencil"></i>
                </button>
                <button type="button" class="btn btn-ghost btn-sm text-danger delete-field" data-index="{{ $index }}" title="{{ __('Delete') }}">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>

        {{-- Field Preview --}}
        <div class="field-preview mt-2">
            @switch($type)
                @case('heading')
                    <h{{ $field['level'] ?? 4 }} class="mb-2">{{ $label }}</h{{ $field['level'] ?? 4 }}>
                    @break
                @case('paragraph')
                    <p class="text-muted">{{ $field['content'] ?? $placeholder ?? 'Paragraph text...' }}</p>
                    @break
                @case('divider')
                    <hr>
                    @break
                @case('section')
                    <hr>
                    <h6 class="mt-3 mb-2 text-muted">{{ $label }}</h6>
                    @break
                @default
                    <div class="form-floating mb-2" style="max-width: 100%;">
                        <input type="text" class="form-control form-control-sm" placeholder="{{ $placeholder ?: $label }}" disabled style="background: var(--color-slate-50);">
                        <label class="form-label small">{{ $label }} @if($required)<span class="text-danger">*</span>@endif</label>
                    </div>
            @endswitch
        </div>
    </div>
</div>