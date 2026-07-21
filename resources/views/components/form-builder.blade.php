{{-- Form Builder Component --}}
@props([
    'formId' => null,
    'formData' => null, // Existing form JSON
    'saveUrl' => null,
    'previewMode' => false,
    'readonly' => false,
    'class' => '',
])

@php
    $builderId = $formId ?? 'form-builder-' . uniqid();
    $defaultFields = [
        'text' => ['label' => 'Text Input', 'icon' => 'bi-type', 'category' => 'basic'],
        'textarea' => ['label' => 'Textarea', 'icon' => 'bi-file-text', 'category' => 'basic'],
        'select' => ['label' => 'Select Dropdown', 'icon' => 'bi-list-ul', 'category' => 'basic'],
        'checkbox' => ['label' => 'Checkbox', 'icon' => 'bi-check-square', 'category' => 'basic'],
        'radio' => ['label' => 'Radio Buttons', 'icon' => 'bi-circle', 'category' => 'basic'],
        'date' => ['label' => 'Date Picker', 'icon' => 'bi-calendar', 'category' => 'basic'],
        'number' => ['label' => 'Number Input', 'icon' => 'bi-123', 'category' => 'basic'],
        'email' => ['label' => 'Email Input', 'icon' => 'bi-envelope', 'category' => 'basic'],
        'tel' => ['label' => 'Phone Input', 'icon' => 'bi-telephone', 'category' => 'basic'],
        'file' => ['label' => 'File Upload', 'icon' => 'bi-file-earmark', 'category' => 'basic'],
        'heading' => ['label' => 'Heading', 'icon' => 'bi-type-h1', 'category' => 'layout'],
        'paragraph' => ['label' => 'Paragraph', 'icon' => 'bi-text-paragraph', 'category' => 'layout'],
        'divider' => ['label' => 'Divider', 'icon' => 'bi-dash', 'category' => 'layout'],
        'section' => ['label' => 'Section Break', 'icon' => 'bi-ui-checks', 'category' => 'layout'],
    ];

    $builderId = $formId ?? 'form-builder-' . uniqid();
    $formData = $formData ?? ['title' => '', 'description' => '', 'fields' => []];
    $formTitle = $formData['title'] ?? 'Untitled Form';
    $formDescription = $formData['description'] ?? '';
    $formFields = $formData['fields'] ?? [];
@endphp

<div
    id="{{ $formId ?? 'form-builder-' . uniqid() }}"
    class="form-builder {{ $class }}"
    data-builder-id="{{ $builderId }}"
    {{ $attributes }}
>
    {{-- Top Toolbar --}}
    <div class="form-builder-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 p-3 bg-white rounded border border-slate-200">
        <div class="d-flex align-items-center gap-3">
            <h4 class="mb-0 fw-semibold">{{ $formTitle ?: 'Untitled Form' }}</h4>
            <span class="badge bg-slate-100 text-slate-600">{{ count($formFields) }} fields</span>
        </div>

        <div class="d-flex gap-2">
            @if(!$readonly)
                <button type="button" class="btn btn-outline-secondary btn-sm" data-action="import-json" title="{{ __('Import JSON') }}">
                    <i class="bi bi-upload me-1"></i> Import
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-action="export-json" title="{{ __('Export JSON') }}">
                    <i class="bi bi-download me-1"></i> Export
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-action="preview" title="{{ __('Preview Form') }}">
                    <i class="bi bi-eye me-1"></i> Preview
                </button>
                <button type="button" class="btn btn-primary" data-action="save" title="{{ __('Save Form') }}">
                    <i class="bi bi-save me-1"></i> Save Form
                </button>
            @endif
        </div>
    </div>

    <div class="form-builder-layout row g-0">
        {{-- Left Sidebar: Field Palette --}}
        <aside class="col-lg-3 border-end form-builder-sidebar p-3" style="background: var(--color-slate-50); min-height: 70vh;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-semibold">{{ __('Field Palette') }}</h6>
                <input type="text" class="form-control form-control-sm" placeholder="{{ __('Search fields...') }}" id="field-search" style="width: auto; min-width: 150px;">
            </div>

            <div class="field-categories">
                @foreach(['basic' => 'Basic Inputs', 'layout' => 'Layout'] as $catKey => $catLabel)
                    <div class="field-category mb-3">
                        <div class="category-header text-uppercase text-muted small fw-semibold mb-2 px-2">
                            {{ $catLabel }}
                        </div>
                        <div class="field-list">
                            @foreach($defaultFields as $key => $field)
                                @if($field['category'] === $catKey)
                                    <button
                                        type="button"
                                        class="field-item d-flex align-items-center gap-2 w-100 p-2 text-start border rounded hover:bg-primary-light transition-colors"
                                        draggable="true"
                                        data-field-type="{{ $key }}"
                                        data-field-label="{{ $field['label'] }}"
                                        data-field-category="{{ $field['category'] }}"
                                    >
                                        <i class="bi {{ $field['icon'] }} text-muted" style="width: 20px;"></i>
                                        <span class="small">{{ $field['label'] }}</span>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </aside>

        {{-- Center: Canvas --}}
        <main class="col-lg-6 form-builder-canvas-wrapper p-3" style="min-height: 70vh;">
            {{-- Form Header Editor --}}
            <div class="form-header-editor mb-4 p-4 bg-white rounded border border-slate-200">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-medium small">{{ __('Form Title') }}</label>
                        <input type="text" class="form-control form-control-lg" id="form-title" value="{{ $formTitle }}" placeholder="{{ __('Enter form title') }}" @if($readonly) readonly @endif>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium small">{{ __('Form ID') }}</label>
                        <input type="text" class="form-control" id="form-id" value="{{ $formData['id'] ?? '' }}" placeholder="{{ __('auto-generated') }}" @if($readonly) readonly @endif>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-medium small">{{ __('Description') }}</label>
                        <textarea class="form-control" id="form-description" rows="2" placeholder="{{ __('Optional description...') }}">{{ $formDescription }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Drop Zone / Field Canvas --}}
            <div
                id="form-canvas"
                class="form-canvas bg-white rounded border border-slate-200 min-h-[400px] p-4"
                data-fields='@json($formFields)'
            >
                @if(empty($formFields))
                    <div class="dropzone-empty text-center py-12">
                        <i class="bi bi-plus-circle fs-1 text-muted mb-3"></i>
                        <h5 class="text-muted mb-2">{{ __('Drop fields here to build your form') }}</h5>
                        <p class="text-muted small">{{ __('Drag fields from the left palette or click to add') }}</p>
                    </div>
                @else
                    <div class="form-fields-list" id="fields-container">
                        @foreach($formFields as $index => $field)
                            @include('components.form-builder.field-renderer', ['field' => $field, 'index' => $index])
                        @endforeach
                    </div>
                @endif

                {{-- Add Field Button (when clicking empty space) --}}
                @if(!empty($formFields))
                    <div class="add-field-btn-wrapper text-center mt-3">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-action="add-field-bottom">
                            <i class="bi bi-plus-lg me-1"></i> Add Field at End
                        </button>
                    </div>
                @endif
            </div>
        </main>

        {{-- Right Sidebar: Field Settings --}}
        <aside class="col-lg-3 border-start form-builder-settings p-3" style="background: var(--color-slate-50); min-height: 70vh;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0 fw-semibold">{{ __('Field Settings') }}</h6>
                <span class="badge bg-slate-100 text-slate-600" id="selected-field-count">{{ __('No field selected') }}</span>
            </div>

            <div id="field-settings-panel" class="settings-panel">
                <div class="text-center text-muted py-5" id="no-selection-message">
                    <i class="bi bi-mouse fs-1 text-muted mb-3"></i>
                    <p class="mb-0">{{ __('Click a field on the canvas to edit its properties') }}</p>
                </div>

                <div id="field-settings-form" style="display: none;">
                    <form id="field-settings-form">
                        <input type="hidden" name="field_index" id="setting-field-index">

                        <div class="mb-3">
                            <label class="form-label fw-medium small">{{ __('Field Type') }}</label>
                            <select class="form-select form-select-sm" id="setting-field-type" disabled>
                                @foreach($defaultFields as $key => $f)
                                    <option value="{{ $key }}">{{ $f['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium small">{{ __('Label') }} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="setting-label" placeholder="{{ __('Field label') }}">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-medium small">{{ __('Name (API key)') }}</label>
                                <input type="text" class="form-control form-control-sm" id="setting-name" placeholder="{{ __('auto-generated') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-medium small">{{ __('Placeholder') }}</label>
                                <input type="text" class="form-control form-control-sm" id="setting-placeholder" placeholder="{{ __('Enter placeholder text') }}">
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="setting-required">
                                    <label class="form-check-label small" for="setting-required">{{ __('Required') }}</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="setting-unique">
                                    <label class="form-check-label small" for="setting-unique">{{ __('Unique') }}</label>
                                </div>
                            </div>

                        <div class="mb-3" id="options-container" style="display: none;">
                            <label class="form-label fw-medium small">{{ __('Options (one per line)') }}</label>
                            <textarea class="form-control form-control-sm" id="setting-options" rows="4" placeholder="Option 1&#10;Option 2&#10;Option 3"></textarea>
                            <div class="form-text">Enter each option on a new line. Format: value|Label</div>
                        </div>

                        <div class="mb-3" id="validation-container" style="display: none;">
                            <label class="form-label fw-medium small">{{ __('Validation Rules') }}</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="val-required">
                                        <label class="form-check-label small" for="val-required">{{ __('Required') }}</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="val-email">
                                        <label class="form-check-label small" for="val-email">{{ __('Email format') }}</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="val-minlength">
                                        <label class="form-check-label small" for="val-minlength">{{ __('Min length') }}</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm" id="val-minlength-val" placeholder="{{ __('Min length') }}" style="display: none;">
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="val-maxlength">
                                        <label class="form-check-label small" for="val-maxlength">{{ __('Max length') }}</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm" id="val-maxlength-val" placeholder="{{ __('Max length') }}" style="display: none;">
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="val-min">
                                        <label class="form-check-label small" for="val-min">{{ __('Min value') }}</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm" id="val-min-val" placeholder="{{ __('Min') }}" style="display: none;">
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="val-max">
                                        <label class="form-check-label small" for="val-max">{{ __('Max value') }}</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm" id="val-max-val" placeholder="{{ __('Max') }}" style="display: none;">
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="val-pattern">
                                        <label class="form-check-label small" for="val-pattern">{{ __('Custom pattern (regex)') }}</label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-sm" id="val-pattern-val" placeholder="{{ __('Regex pattern') }}" style="display: none;">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-medium small">{{ __('Help Text') }}</label>
                            <textarea class="form-control form-control-sm" id="setting-help" rows="2" placeholder="{{ __('Help text shown below field') }}"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="save-field-settings">
                                <i class="bi bi-save me-1"></i> Save Changes
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="delete-field-btn" style="display: none;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </aside>
    </div>

    {{-- Preview Modal --}}
    <div class="modal fade" id="preview-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Form Preview') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="preview-container"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                </div>
            </div>
        </div>

        {{-- JSON Modal --}}
        <div class="modal fade" id="json-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Form JSON') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="input-group mb-3">
                            <button class="btn btn-outline-secondary" id="copy-json"><i class="bi bi-clipboard me-1"></i> {{ __('Copy JSON') }}</button>
                            <button class="btn btn-outline-secondary" id="download-json"><i class="bi bi-download me-1"></i> {{ __('Download .json') }}</button>
                            <input type="file" class="form-control d-none" id="json-file-input" accept=".json">
                            <button class="btn btn-outline-secondary" id="import-json-btn"><i class="bi bi-upload me-1"></i> {{ __('Import') }}</button>
                        </div>
                        <textarea class="form-control font-monospace" id="json-editor" rows="25" spellcheck="false"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
                        <button type="button" class="btn btn-primary" id="apply-json">{{ __('Apply Changes') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const builderId = '{{ $builderId }}';
    const builderEl = document.getElementById('{{ $builderId }}');
    if (!builderEl) return;

    let fields = @json($formFields);
    let selectedFieldIndex = null;

    // DOM Elements
    const canvas = document.getElementById('form-canvas');
    const fieldsContainer = document.getElementById('fields-container');
    const settingsPanel = document.getElementById('field-settings-panel');
    const settingsForm = document.getElementById('field-settings-form');
    const noSelection = document.getElementById('no-selection-message');
    const formEl = document.getElementById('wizard-{{ $builderId }}-form') || document.querySelector('.wizard-form');

    // ─── Render Field on Canvas ───
    function renderField(field, index) {
        const div = document.createElement('div');
        div.className = 'form-field-item = 'form-field-item mb-3 p-3 border rounded bg-white position-relative';
        div.dataset.index = field.index;
        div.dataset.fieldType = field.type;
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-slate-100 text-slate-600">{{ field.type }}</span>
                    <strong>{{ field.label }}</strong>
                    {{ field.required ? '<span class="badge bg-danger">Required</span>' : '' }}
                </div>
                <div class="field-actions">
                    <button type="button" class="btn btn-ghost btn-sm edit-field" data-index="${{ field.index }}" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm text-danger delete-field" data-index="${{ field.index }}" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;

        // Render field preview based on type
        const preview = document.createElement('div');
        preview.className = 'field-preview mt-2';
        preview.innerHTML = renderFieldPreview(field);
        div.appendChild(preview);

        return div.outerHTML;
    }

    function renderFieldPreview(field) {
        switch (field.type) {
            case 'heading':
                return `<h${field.level || 4} class="mb-2">${field.label}</h${field.level || 4}>`;
            case 'paragraph':
                return `<p class="text-muted">${field.content || field.placeholder || 'Paragraph text...'}</p>`;
            case 'divider':
                return '<hr>';
            case 'section':
                return `<hr><h6 class="mt-3 mb-2 text-muted">${field.label}</h6>`;
            default:
                return `
                    <div class="form-floating mb-2" style="max-width: 100%;">
                        <input type="text" class="form-control form-control-sm" placeholder="${field.placeholder || field.label}" disabled style="background: var(--color-slate-50);">
                        <label class="form-label small">${field.label}${field.required ? ' *' : ''}</label>
                    </div>
                `;
        }
    }

    function renderFields() {
        const container = document.getElementById('fields-container');
        if (!fields.length) {
            container.innerHTML = `
                <div class="dropzone-empty text-center py-12">
                    <i class="bi bi-plus-circle fs-1 text-muted mb-3"></i>
                    <h5 class="text-muted mb-2">Drop fields here to build your form</h5>
                    <p class="text-muted small">Drag fields from the left palette or click to add</p>
                </div>
                <div class="add-field-btn-wrapper text-center mt-3">
                    <button type="button" class="btn btn-outline-primary btn-sm" data-action="add-field-bottom">
                        <i class="bi bi-plus-lg me-1"></i> Add Field at End
                    </button>
                </div>`;
            return;
        }

        container.innerHTML = fields.map((f, i) => renderField({...f, index: i}, i)).join('');

        // Attach event listeners
        container.querySelectorAll('.edit-field').forEach(btn => {
            btn.addEventListener('click', () => openFieldSettings(parseInt(btn.dataset.index)));
        });
        container.querySelectorAll('.delete-field').forEach(btn => {
            btn.addEventListener('click', () => deleteField(parseInt(btn.dataset.index)));
        });

        // Make fields draggable for reordering
        new Sortable(document.getElementById('fields-container'), {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function(evt) {
                const oldIndex = evt.oldIndex;
                const newIndex = evt.newIndex;
                if (oldIndex !== newIndex) {
                    const [moved] = fields.splice(oldIndex, 1);
                    fields.splice(newIndex, 0, moved);
                    reindexFields();
                    saveDraft();
                }
            });
        });
    }

    // ─── Field Settings Panel ───
    function openFieldSettings(index) {
        const field = fields[index];
        if (!field) return;

        selectedFieldIndex = field.index;
        document.getElementById('no-selection-message').style.display = 'none';
        document.getElementById('field-settings-form').style.display = 'block';
        document.getElementById('delete-field-btn').style.display = 'inline-block';
        document.getElementById('selected-field-count').textContent = `Editing: ${field.label}`;

        // Populate form
        document.getElementById('setting-field-index').value = field.index;
        document.getElementById('setting-field-type').value = field.type;
        document.getElementById('setting-label').value = field.label;
        document.getElementById('setting-name').value = field.name || field.label.toLowerCase().replace(/\s+/g, '_');
        document.getElementById('setting-placeholder').value = field.placeholder || '';
        document.getElementById('setting-required').checked = field.required || false;
        document.getElementById('setting-unique').checked = field.unique || false;
        document.getElementById('setting-help').value = field.help || '';

        // Options
        const optionsContainer = document.getElementById('options-container');
        if (['select', 'radio', 'checkbox'].includes(field.type)) {
            optionsContainer.style.display = 'block';
            document.getElementById('setting-options').value = (field.options || []).map(o => `${o.value}|${o.label}`).join('\n');
        } else {
            optionsContainer.style.display = 'none';
        }

        // Validation
        const validation = field.validation || {};
        Object.keys(document.querySelectorAll('#validation-container input[type="checkbox"]')).forEach(cb => {
            cb.checked = !!validation[cb.id.replace('val-', '')];
        });
        document.getElementById('val-minlength-val').style.display = validation.minlength ? 'block' : 'none';
        document.getElementById('val-maxlength-val').style.display = validation.maxlength ? 'block' : 'none';
        document.getElementById('val-min-val').style.display = validation.min !== undefined ? 'block' : 'none';
        document.getElementById('val-max-val').style.display = validation.max !== undefined ? 'block' : 'none';
        document.getElementById('val-pattern-val').style.display = validation.pattern ? 'block' : 'none';

        // Show validation inputs conditionally
        document.querySelectorAll('#validation-container input[type="checkbox"]').forEach(cb => {
            cb.addEventListener('change', function() {
                const valInput = document.getElementById(this.id + '-val');
                if (valInput) valInput.style.display = this.checked ? 'block' : 'none';
            });
        });

        // Show/hide options based on type
        document.getElementById('setting-field-type').onchange = function() {
            const val = this.value;
            document.getElementById('options-container').style.display = ['select', 'radio', 'checkbox'].includes(val) ? 'block' : 'none';
        };
    }

    function closeFieldSettings() {
        document.getElementById('no-selection-message').style.display = 'block';
        document.getElementById('field-settings-form').style.display = 'none';
        document.getElementById('delete-field-btn').style.display = 'none';
        document.getElementById('selected-field-count').textContent = 'No field selected';
        selectedFieldIndex = null;
    }

    function saveFieldSettings() {
        if (selectedFieldIndex === null) return;

        const field = fields[selectedFieldIndex];
        const updates = {
            label: document.getElementById('setting-label').value,
            name: document.getElementById('setting-name').value,
            placeholder: document.getElementById('setting-placeholder').value,
            required: document.getElementById('setting-required').checked,
            unique: document.getElementById('setting-unique').checked,
            help: document.getElementById('setting-help').value,
        };

        // Options for select/radio/checkbox
        const type = document.getElementById('setting-field-type').value;
        if (['select', 'radio', 'checkbox'].includes(type)) {
            const optionsText = document.getElementById('setting-options').value;
            updates.options = optionsText.split('\n')
                .filter(l => l.trim())
                .map((line, i) => {
                    const parts = line.split('|');
                    return { value: parts[0]?.trim() || `opt${i}`, label: parts[1]?.trim() || parts[0]?.trim() || `Option ${i+1}` };
                });
        }

        // Validation
        const validation = {};
        document.querySelectorAll('#validation-container input[type="checkbox"]').forEach(cb => {
            if (cb.checked) {
                const key = cb.id.replace('val-', '');
                if (['minlength', 'maxlength', 'min', 'max', 'pattern'].includes(key)) {
                    const val = document.getElementById(cb.id + '-val').value;
                    if (val) validation[key] = isNaN(val) ? val : parseFloat(val);
                } else {
                    validation[key] = true;
                }
            });
        if (Object.keys(validation).length) {
            field.validation = validation;
        }

        Object.assign(field, updates);
        renderFields();
        closeFieldSettings();
        saveDraft();
        showToast('success', 'Field updated');
    }

    function deleteField(index) {
        if (confirm('Delete this field?')) {
            fields.splice(index, 1);
            reindexFields();
            renderFields();
            closeFieldSettings();
            saveDraft();
        }
    }

    function reindexFields() {
        fields.forEach((f, i) => f.index = i);
    }

    // ─── Drag & Drop ───
    function initDragDrop() {
        // From palette to canvas
        document.querySelectorAll('.field-item').forEach(item => {
            item.addEventListener('dragstart', e => {
                e.dataTransfer.setData('text/plain', JSON.stringify({
                    type: e.target.closest('.field-item').dataset.fieldType,
                    label: e.target.closest('.field-item').dataset.fieldLabel,
                    category: e.target.closest('.field-item').dataset.fieldCategory
                }));
                e.target.classList.add('dragging');
            });
            item.addEventListener('dragend', () => item.classList.remove('dragging'));
        });

        const canvas = document.getElementById('form-canvas');
        ['dragover', 'dragenter'].forEach(e => canvas.addEventListener(e, e => {
            e.preventDefault();
            canvas.classList.add('dragover');
        }));
        ['dragleave', 'drop'].forEach(e => canvas.addEventListener(e, e => {
            canvas.classList.remove('dragover');
        }));
        canvas.addEventListener('drop', e => {
            e.preventDefault();
            canvas.classList.remove('dragover');
            try {
                const data = JSON.parse(e.dataTransfer.getData('text/plain'));
                addField(data.type, { label: data.label });
            } catch (e) {}
        });

        // Click canvas to add field at end
        canvas.addEventListener('click', e => {
            if (e.target === canvas || e.target.closest('.dropzone-empty')) {
                // Show add field modal or just add default
            }
        });
    }

    function addField(type, config = {}) {
        const newField = {
            index: fields.length,
            type: type,
            label: config.label || 'New Field',
            name: config.name || config.label.toLowerCase().replace(/\s+/g, '_'),
            required: false,
            unique: false,
            placeholder: '',
            help: '',
            options: ['select', 'radio', 'checkbox'].includes(type) ? [{value: 'opt1', label: 'Option 1'}] : [],
            validation: {}
        };
        fields.push(newField);
        reindexFields();
        renderFields();
        saveDraft();
    }

    // ─── Draft Management ───
    const draftKey = 'form-builder-draft-{{ $builderId }}';
    function saveDraft() {
        localStorage.setItem(draftKey, JSON.stringify({
            fields: fields,
            title: document.getElementById('form-title')?.value,
            description: document.getElementById('form-description')?.value,
            _timestamp: Date.now()
        }));
    }

    function loadDraft() {
        const saved = localStorage.getItem('form-builder-draft-{{ $builderId }}');
        if (saved) {
            try {
                const data = JSON.parse(saved);
                if (data._timestamp && Date.now() - data._timestamp < 7 * 24 * 60 * 60 * 1000) {
                    if (data.fields) fields = data.fields;
                    if (data.title) document.getElementById('form-title').value = data.title;
                    if (data.description) document.getElementById('form-description').value = data.description;
                    renderFields();
                }
            } catch (e) {}
        }
    }

    // ─── Export/Import ───
    function exportForm() {
        const data = {
            title: document.getElementById('form-title').value,
            description: document.getElementById('form-description').value,
            fields: fields.map(f => {
                const { index, ...rest } = f;
                return rest;
            }),
            version: '1.0',
            exported: new Date().toISOString()
        };
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${document.getElementById('form-title').value || 'form'}.json`;
        a.click();
        URL.revokeObjectURL(url);
    }

    function importForm(json) {
        try {
            const data = typeof json === 'string' ? JSON.parse(json) : json;
            if (data.fields) {
                fields = data.fields.map((f, i) => ({ ...f, index: i }));
                document.getElementById('form-title').value = data.title || '';
                document.getElementById('form-description').value = data.description || '';
                renderFields();
                saveDraft();
                showToast('success', 'Form imported successfully');
            }
        } catch (e) {
            showToast('error', 'Invalid JSON file');
        }
    }

    function copyJson() {
        const data = {
            title: document.getElementById('form-title').value,
            description: document.getElementById('form-description').value,
            fields: fields.map(f => { const { index, ...r } = f; return r; })
        };
        navigator.clipboard.writeText(JSON.stringify(data, null, 2));
        showToast('success', 'JSON copied to clipboard');
    }

    // ─── Preview ───
    function showPreview() {
        const modal = new bootstrap.Modal(document.getElementById('preview-modal'));
        const container = document.getElementById('preview-container');

        container.innerHTML = fields.map(f => `
            <div class="mb-4 p-3 border rounded bg-white">
                ${renderFieldPreview({...f, type: f.type, label: f.label, placeholder: f.placeholder, required: f.required})}
            </div>
        `).join('');

        modal.show();
    }

    // ─── JSON Modal ───
    function openJsonModal() {
        const data = {
            title: document.getElementById('form-title').value,
            description: document.getElementById('form-description').value,
            fields: fields.map(f => { const { index, ...r } = f; return r; }),
            version: '1.0'
        };
        document.getElementById('json-editor').value = JSON.stringify(data, null, 2);
        new bootstrap.Modal(document.getElementById('json-modal')).show();
    }

    function applyJson() {
        try {
            const json = document.getElementById('json-editor').value;
            importForm(json);
            bootstrap.Modal.getInstance(document.getElementById('json-modal')).hide();
        } catch (e) {
            showToast('error', 'Invalid JSON');
        }
    }

    function downloadJson() {
        const data = {
            title: document.getElementById('form-title').value,
            description: document.getElementById('form-description').value,
            fields: fields.map(f => { const { index, ...r } = f; return r; }),
            version: '1.0',
            exported: new Date().toISOString()
        };
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${document.getElementById('form-title').value || 'form'}.json`;
        a.click();
        URL.revokeObjectURL(url);
    }

    function importJson() {
        document.getElementById('json-file-input').click();
    }

    // ─── Initialize ───
    function init() {
        loadDraft();
        renderFields();
        initDragDrop();

        // Toolbar buttons
        document.querySelector('[data-action="save"]')?.addEventListener('click', saveForm);
        document.querySelector('[data-action="export-json"]')?.addEventListener('click', exportForm);
        document.querySelector('[data-action="import-json"]')?.addEventListener('click', () => document.getElementById('json-file-input').click());
        document.querySelector('[data-action="preview"]')?.addEventListener('click', showPreview);
        document.querySelector('[data-action="import-json"]')?.addEventListener('click', () => document.getElementById('json-file-input').click());
        document.getElementById('json-file-input')?.addEventListener('change', e => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => importForm(e.target.result);
                reader.readAsText(file);
            });
        });

        document.getElementById('export-json')?.addEventListener('click', exportForm);
        document.getElementById('download-json')?.addEventListener('click', downloadJson);
        document.getElementById('copy-json')?.addEventListener('click', copyJson);
        document.getElementById('import-json-btn')?.addEventListener('click', () => document.getElementById('json-file-input').click());
        document.getElementById('apply-json')?.addEventListener('click', applyJson);
        document.getElementById('copy-json')?.addEventListener('click', copyJson);
        document.getElementById('download-json')?.addEventListener('click', downloadJson);
        document.getElementById('import-json-btn')?.addEventListener('click', () => document.getElementById('json-file-input').click());

        // Settings
        document.getElementById('save-field-settings')?.addEventListener('click', saveFieldSettings);
        document.getElementById('delete-field-btn')?.addEventListener('click', () => {
            if (selectedFieldIndex !== null) deleteField(selectedFieldIndex);
        });

        // Settings form changes
        document.getElementById('setting-field-type')?.addEventListener('change', function() {
            document.getElementById('options-container').style.display = ['select', 'radio', 'checkbox'].includes(this.value) ? 'block' : 'none';
        });

        // Validation toggles
        document.querySelectorAll('#validation-container input[type="checkbox"]').forEach(cb => {
            cb.addEventListener('change', function() {
                const valInput = document.getElementById(this.id + '-val');
                if (valInput) valInput.style.display = this.checked ? 'block' : 'none';
            });
        });

        // Auto-save draft
        const form = document.getElementById('wizard-{{ $builderId }}-form');
        form?.addEventListener('input', debounce(saveDraft, 2000));
        form?.addEventListener('change', saveDraft);

        // Form title/description changes
        document.getElementById('form-title')?.addEventListener('input', () => {
            document.querySelector('.wizard-title')?.textContent = document.getElementById('form-title').value || 'Untitled Form';
        });

        renderFields();
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush