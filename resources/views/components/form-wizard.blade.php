{{-- Form Wizard Component --}}
@props([
    'steps' => [],
    'currentStep' => 1,
    'action' => null,
    'method' => 'POST',
    'id' => null,
    'class' => '',
    'saveDraft' => true,
    'draftKey' => null,
    'showProgress' => true,
    'allowSkip' => false,
    'validateOnNext' => true,
    'confirmOnExit' => true,
    'completeUrl' => null,
    'class' => '',
])

@php
    $wizardId = $id ?? 'wizard-' . uniqid();
    $totalSteps = count($steps);
    $draftKey = $draftKey ?? 'wizard-draft-' . ($id ?? 'form');
    $currentStepIndex = max(1, min($currentStep, count($steps)));

    // Validate steps structure
    foreach ($steps as $index => $step) {
        $steps[$index] = array_merge([
            'key' => 'step-' . ($index + 1),
            'label' => 'Step ' . ($index + 1),
            'icon' => null,
            'description' => null,
            'fields' => [],
            'validation' => [],
            'optional' => false,
        ], $step);
    }

    $progressPercent = ($currentStepIndex / count($steps)) * 100;
@endphp

<div
    id="{{ $wizardId }}"
    class="form-wizard {{ $class }}"
    data-wizard-id="{{ $wizardId }}"
    data-total-steps="{{ count($steps) }}"
    data-current-step="{{ $currentStepIndex }}"
    data-save-draft="{{ $saveDraft ? 'true' : 'false' }}"
    data-draft-key="{{ $draftKey }}"
    data-validate-on-next="{{ $validateOnNext ? 'true' : 'false' }}"
    data-confirm-exit="{{ $confirmOnExit ? 'true' : 'false' }}"
    {{ $attributes }}
>
    {{-- Progress Bar --}}
    @if($showProgress)
        <div class="wizard-progress mb-4" role="progressbar" aria-valuenow="{{ $currentStepIndex }}" aria-valuemin="1" aria-valuemax="{{ count($steps) }}" aria-label="Form progress">
            <div class="progress-track" role="presentation">
                <div class="progress-fill" style="width: {{ $progressPercent }}%;" aria-hidden="true"></div>
            </div>
            <div class="progress-steps d-flex justify-content-between mt-2" role="list">
                @foreach($steps as $index => $step)
                    @php
                        $stepNum = $index + 1;
                        $isActive = $stepNum === $currentStepIndex;
                        $isCompleted = $stepNum < $currentStepIndex;
                        $isFuture = $stepNum > $currentStepIndex;
                    @endphp
                    <div class="progress-step {{ $isActive ? 'active' : '' }} {{ $isCompleted ? 'completed' : '' }} {{ $isFuture ? 'future' : '' }}" role="listitem">
                        <div class="step-circle" aria-hidden="true">
                            @if($isCompleted)
                                <i class="bi bi-check"></i>
                            @else
                                {{ $stepNum }}
                            @endif
                        </div>
                        <div class="step-label">{{ $step['label'] }}</div>
                        @if(!$isCompleted && !$isActive && !$isFuture)
                            <div class="step-connector"></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form
        id="{{ $wizardId }}-form"
        action="{{ $action }}"
        method="{{ $method }}"
        class="wizard-form"
        novalidate
        @if($confirmOnExit) data-confirm-exit="true" @endif
    >
        @csrf
        @if($method !== 'POST' && $method !== 'GET')
            <input type="hidden" name="_method" value="{{ strtoupper($method) }}">
        @endif

        {{-- Hidden step tracker --}}
        <input type="hidden" name="wizard_step" value="{{ $currentStepIndex }}" id="{{ $wizardId }}-step-input">
        <input type="hidden" name="wizard_completed" value="false" id="{{ $wizardId }}-completed-input">

        {{-- Step Panels --}}
        <div class="wizard-steps" role="tabpanel">
            @foreach($steps as $index => $step)
                @php
                    $stepNum = $index + 1;
                    $isActive = $stepNum === $currentStepIndex;
                    $isCompleted = $stepNum < $currentStepIndex;
                @endphp
                <div
                    class="wizard-step-panel {{ $isActive ? 'active' : '' }} {{ $isCompleted ? 'completed' : '' }}"
                    role="tabpanel"
                    id="step-{{ $stepNum }}"
                    aria-hidden="{{ !$isActive }}"
                    data-step="{{ $stepNum }}"
                    data-key="{{ $step['key'] }}"
                >
                    @if(!$isCompleted && !$isActive)
                        <div class="step-locked text-center py-5">
                            <i class="bi bi-lock fs-1 text-muted"></i>
                            <h5 class="mt-3">{{ $steps[$index]['label'] }}</h5>
                            <p class="text-muted">Complete previous steps to unlock</p>
                        </div>
                    @else
                        <div class="step-header mb-4">
                            @if($step['icon'])
                                <div class="step-icon mb-2">
                                    <i class="bi bi-{{ $step['icon'] }} fs-2 text-primary"></i>
                                </div>
                            @endif
                            <h3 class="h5 mb-1">{{ $step['label'] }}</h3>
                            @if($step['description'])
                                <p class="text-muted small mb-0">{{ $step['description'] }}</p>
                            @endif
                        </div>

                        {{-- Step Fields --}}
                        <div class="step-fields">
                            @foreach($step['fields'] as $field)
                                @php
                                    $fieldId = 'field-' . $field['name'];
                                    $fieldName = $field['name'];
                                    $fieldValue = old($fieldName, $field['value'] ?? '');
                                    $fieldLabel = $field['label'] ?? ucfirst(str_replace('_', ' ', $fieldName));
                                    $fieldType = $field['type'] ?? 'text';
                                    $fieldRequired = $field['required'] ?? false;
                                    $fieldPlaceholder = $field['placeholder'] ?? '';
                                    $fieldHelp = $field['help'] ?? null;
                                    $fieldOptions = $field['options'] ?? [];
                                    $fieldValidation = $field['validation'] ?? [];
                                    $fieldClass = $field['class'] ?? '';
                                    $fieldCols = $field['cols'] ?? 12;
                                @endphp
                                <div class="col-md-{{ $fieldCols }} mb-3">
                                    <label for="{{ $fieldId }}" class="form-label">
                                        {{ $fieldLabel }}
                                        @if($fieldRequired)
                                            <span class="text-danger ms-1" aria-hidden="true">*</span>
                                        @endif
                                    </label>
                                    @switch($fieldType)
                                        @case('textarea')
                                            <textarea
                                                id="{{ $fieldId }}"
                                                name="{{ $fieldName }}"
                                                class="form-control"
                                                rows="{{ $field['rows'] ?? 3 }}"
                                                placeholder="{{ $fieldPlaceholder }}"
                                                @if(isset($fieldValidation['required'])) required @endif
                                                @if(isset($fieldValidation['minlength'])) minlength="{{ $fieldValidation['minlength'] }}" @endif
                                                @if(isset($fieldValidation['maxlength'])) maxlength="{{ $fieldValidation['maxlength'] }}" @endif
                                            >{{ $fieldValue }}</textarea>
                                            @break
                                        @case('select')
                                            <select
                                                id="{{ $fieldId }}"
                                                name="{{ $fieldName }}"
                                                class="form-select"
                                                @if(isset($fieldValidation['required'])) required @endif
                                            >
                                                <option value="">-- Select --</option>
                                                @foreach($fieldOptions as $optValue => $optLabel)
                                                    <option value="{{ $optValue }}" {{ (string)$optValue === (string)$fieldValue ? 'selected' : '' }}>{{ $optLabel }}</option>
                                                @endforeach
                                            </select>
                                            @break
                                        @case('checkbox')
                                            <div class="form-check">
                                                <input
                                                    type="checkbox"
                                                    id="{{ $fieldId }}"
                                                    name="{{ $fieldName }}"
                                                    class="form-check-input"
                                                    @if($fieldValue) checked @endif
                                                    @if($fieldValidation['required'] ?? false) required @endif
                                                >
                                                <label class="form-check-label" for="{{ $fieldId }}">{{ $fieldLabel }}</label>
                                            </div>
                                            @break
                                        @case('radio')
                                            <div>
                                                @foreach($fieldOptions as $optValue => $optLabel)
                                                    <div class="form-check form-check-inline">
                                                        <input
                                                            class="form-check-input"
                                                            type="radio"
                                                            name="{{ $fieldName }}"
                                                            id="{{ $fieldId }}-{{ $optValue }}"
                                                            value="{{ $optValue }}"
                                                            @if((string)$optValue === (string)$fieldValue) checked @endif
                                                        >
                                                        <label class="form-check-label" for="{{ $fieldId }}-{{ $optValue }}">{{ $optLabel }}</label>
                                                    </div>
                                                @endforeach
                                            </div>
                                            @break
                                        @case('date')
                                            <input
                                                type="date"
                                                id="{{ $fieldId }}"
                                                name="{{ $fieldName }}"
                                                class="form-control"
                                                value="{{ $fieldValue }}"
                                                @if(isset($fieldValidation['required'])) required @endif
                                                @if(isset($fieldValidation['min'])) min="{{ $fieldValidation['min'] }}" @endif
                                                @if(isset($fieldValidation['max'])) max="{{ $fieldValidation['max'] }}" @endif
                                            >
                                            @break
                                        @case('number')
                                            <input
                                                type="number"
                                                id="{{ $fieldId }}"
                                                name="{{ $fieldName }}"
                                                class="form-control"
                                                value="{{ $fieldValue }}"
                                                @if(isset($fieldValidation['required'])) required @endif
                                                @if(isset($fieldValidation['min'])) min="{{ $fieldValidation['min'] }}" @endif
                                                @if(isset($fieldValidation['max'])) max="{{ $fieldValidation['max'] }}" @endif
                                                @if(isset($fieldValidation['step'])) step="{{ $fieldValidation['step'] }}" @endif
                                            >
                                            @break
                                        @case('email')
                                            <input
                                                type="email"
                                                id="{{ $fieldId }}"
                                                name="{{ $fieldName }}"
                                                class="form-control"
                                                value="{{ $fieldValue }}"
                                                @if(isset($fieldValidation['required'])) required @endif
                                            >
                                            @break
                                        @case('tel')
                                            <input
                                                type="tel"
                                                id="{{ $fieldId }}"
                                                name="{{ $fieldName }}"
                                                class="form-control"
                                                value="{{ $fieldValue }}"
                                                @if(isset($fieldValidation['required'])) required @endif
                                            >
                                            @break
                                        @case('file')
                                            <input
                                                type="file"
                                                id="{{ $fieldId }}"
                                                name="{{ $fieldName }}"
                                                class="form-control"
                                                @if(isset($fieldValidation['required'])) required @endif
                                                @if(isset($field['accept'])) accept="{{ $field['accept'] }}" @endif
                                            >
                                            @break
                                        @default
                                            <input
                                                type="text"
                                                id="{{ $fieldId }}"
                                                name="{{ $fieldName }}"
                                                class="form-control"
                                                value="{{ $fieldValue }}"
                                                placeholder="{{ $fieldPlaceholder }}"
                                                @if(isset($fieldValidation['required'])) required @endif
                                                @if(isset($fieldValidation['minlength'])) minlength="{{ $fieldValidation['minlength'] }}" @endif
                                                @if(isset($fieldValidation['maxlength'])) maxlength="{{ $fieldValidation['maxlength'] }}" @endif
                                                @if(isset($fieldValidation['pattern'])) pattern="{{ $fieldValidation['pattern'] }}" @endif
                                            >
                                    @endswitch

                                    @if($fieldHelp)
                                        <div class="form-text">{{ $fieldHelp }}</div>
                                    @endif

                                    {{-- Validation messages will be inserted here by JS --}}
                                    <div class="field-error text-danger small mt-1" id="{{ $fieldId }}-error" aria-live="polite"></div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Navigation --}}
        <div class="wizard-navigation d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
            {{-- Previous Button --}}
            <div class="nav-previous">
                @if($currentStepIndex > 1)
                    <button
                        type="button"
                        class="btn btn-outline-secondary wizard-prev"
                        data-wizard-action="prev"
                    >
                        <i class="bi bi-chevron-left me-1"></i> Previous
                    </button>
                @else
                    <div style="width: 120px;"></div>
                @endif
            </div>

            {{-- Step Indicator --}}
            <div class="wizard-step-indicator text-center text-muted small">
                Step {{ $currentStepIndex }} of {{ count($steps) }}
            </div>

            {{-- Next/Submit Buttons --}}
            <div class="nav-next">
                @if($currentStepIndex < count($steps))
                    <button
                        type="button"
                        class="btn btn-primary wizard-next"
                        data-wizard-action="next"
                    >
                        Next <i class="bi bi-chevron-right ms-1"></i>
                    </button>
                @else
                    <button
                        type="submit"
                        class="btn btn-primary wizard-submit"
                        data-wizard-action="submit"
                    >
                        <i class="bi bi-check-lg me-1"></i> Submit
                    </button>
                    @if($saveDraft)
                        <button
                            type="button"
                            class="btn btn-outline-secondary ms-2"
                            data-wizard-action="save-draft"
                        >
                            <i class="bi bi-save me-1"></i> Save Draft
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function() {
    // ─── Form Wizard Logic ───
    const wizardId = '{{ $wizardId }}';
    const wizardEl = document.getElementById(wizardId);
    const form = wizardEl?.querySelector('.wizard-form');
    const stepInput = document.getElementById('{{ $wizardId }}-step-input');
    const completedInput = document.getElementById('{{ $wizardId }}-completed-input');

    if (!wizardEl || !form) return;

    const totalSteps = parseInt(wizardEl.dataset.totalSteps || '1', 10);
    let currentStep = parseInt(wizardEl.dataset.currentStep || '1', 10);
    const saveDraft = wizardEl.dataset.saveDraft === 'true';
    const draftKey = wizardEl.dataset.draftKey || 'wizard-draft';
    const validateOnNext = wizardEl.dataset.validateOnNext === 'true';
    const confirmExit = wizardEl.dataset.confirmExit === 'true';

    const panels = wizardEl.querySelectorAll('.wizard-step-panel');
    const progressFill = wizardEl.querySelector('.progress-fill');
    const progressSteps = wizardEl.querySelectorAll('.progress-step');
    const stepIndicator = wizardEl.querySelector('.wizard-step-indicator');
    const stepInput = wizardEl.querySelector('[data-wizard-id]')?.querySelector('input[name="wizard_step"]');
    const completedInput = form?.querySelector('input[name="wizard_completed"]');

    // ─── Draft Management ───
    function saveDraft() {
        if (!saveDraft) return;
        const formData = new FormData(form);
        const draftData = {};
        for (const [key, value] of formData.entries()) {
            if (key !== '_token' && key !== '_method' && key !== 'wizard_step' && key !== 'wizard_completed') {
                draftData[key] = value;
            }
        }
        draftData._wizard_step = currentStep;
        draftData._timestamp = Date.now();
        localStorage.setItem(draftKey, JSON.stringify(draftData));
    }

    function loadDraft() {
        try {
            const saved = localStorage.getItem(draftKey);
            if (saved) {
                const data = JSON.parse(saved);
                // Only restore if recent (within 7 days)
                if (data._timestamp && Date.now() - data._timestamp < 7 * 24 * 60 * 60 * 1000) {
                    return data;
                }
            }
        } catch (e) {
            console.warn('Draft load failed:', e);
        }
        return null;
    }

    function clearDraft() {
        localStorage.removeItem(draftKey);
    }

    // ─── Step Navigation ───
    function showStep(stepNum, saveCurrent = true) {
        if (stepNum < 1 || stepNum > {{ count($steps) }}) return false;

        // Validate current step before leaving
        if (saveCurrent && !validateCurrentStep()) {
            return false;
        }

        // Save draft before switching
        if (saveCurrent) {
            saveDraft();
        }

        const oldStep = currentStep;
        currentStep = stepNum;

        // Update panels
        document.querySelectorAll('.wizard-step-panel').forEach(panel => {
            const panelStep = parseInt(panel.dataset.step, 10);
            const isActive = parseInt(panel.dataset.step, 10) === stepNum;
            panel.classList.toggle('active', isActive);
            panel.classList.toggle('completed', parseInt(panel.dataset.step, 10) < stepNum);
            panel.setAttribute('aria-hidden', !isActive);
        });

        // Update progress
        updateProgress(stepNum);

        // Update URL/history (optional)
        // history.replaceState(null, '', `?step=${stepNum}`);

        // Focus first input in new step
        setTimeout(() => {
            const activePanel = document.querySelector('.wizard-step-panel.active');
            const firstInput = activePanel?.querySelector('input, select, textarea');
            firstInput?.focus();
        }, 100);

        return true;
    }

    function nextStep() {
        if (currentStep < {{ count($steps) }}) {
            return showStep(currentStep + 1);
        }
        return false;
    }

    function prevStep() {
        if (currentStep > 1) {
            return showStep(currentStep - 1);
        }
        return false;
    }

    function goToStep(stepNum) {
        // Only allow going back or to next step
        if (stepNum <= currentStep + 1) {
            return showStep(stepNum);
        }
        return false;
    }

    // ─── Validation ───
    function validateCurrentStep() {
        const activePanel = document.querySelector('.wizard-step-panel.active');
        if (!activePanel) return true;

        const inputs = activePanel.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;

        activePanel.querySelectorAll('.field-error').forEach(el => el.remove());
        activePanel.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

        activePanel.querySelectorAll('input[required], select[required], textarea[required]').forEach(input => {
            if (!input.value.trim()) {
                showFieldError(input, 'This field is required');
                isValid = false;
            } else if (input.type === 'email' && input.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value)) {
                showFieldError(input, 'Invalid email format');
                isValid = false;
            } else if (input.hasAttribute('minlength') && input.value.length < parseInt(input.getAttribute('minlength'))) {
                showFieldError(input, 'Minimum length is ' + input.getAttribute('minlength'));
                isValid = false;
            } else if (input.hasAttribute('maxlength') && input.value.length > parseInt(input.getAttribute('maxlength'))) {
                showFieldError(input, 'Maximum length is ' + input.getAttribute('maxlength'));
                isValid = false;
            } else if (input.type === 'number') {
                const min = input.getAttribute('min');
                const max = input.getAttribute('max');
                const val = parseFloat(input.value);
                if (input.getAttribute('min') !== null && val < parseFloat(input.getAttribute('min'))) {
                    showFieldError(input, 'Value must be at least ' + input.getAttribute('min'));
                    isValid = false;
                }
                if (input.getAttribute('max') !== null && val > parseFloat(input.getAttribute('max'))) {
                    showFieldError(input, 'Value must be at most ' + input.getAttribute('max'));
                    isValid = false;
                }
            }
        });

        return isValid;
    }

    function showFieldError(input, message) {
        input.classList.add('is-invalid');
        const errorId = input.id + '-error';
        let errorEl = document.getElementById(input.id + '-error');
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.id = input.id + '-error';
            errorEl.className = 'field-error text-danger small mt-1';
            input.parentNode.appendChild(errorEl);
        }
        errorEl.textContent = input.getAttribute('data-error-message') || 'Invalid input';
    }

    function clearFieldError(input) {
        input.classList.remove('is-invalid');
        const errorEl = document.getElementById(input.id + '-error');
        if (errorEl) errorEl.remove();
    }

    // ─── Progress UI ───
    function updateProgress(stepNum) {
        const percent = (stepNum / {{ count($steps) }}) * 100;
        const progressFill = document.querySelector('.progress-fill');
        if (progressFill) {
            progressFill.style.width = percent + '%';
        }

        document.querySelectorAll('.progress-step').forEach((stepEl, index) => {
            const stepNum = index + 1;
            stepEl.classList.toggle('active', stepEl.dataset.step == currentStep);
            stepEl.classList.toggle('completed', stepNum < currentStep);
            stepEl.classList.toggle('future', stepNum > currentStep);
        });

        const stepIndicator = document.querySelector('.wizard-step-indicator');
        if (stepIndicator) {
            stepIndicator.textContent = 'Step ' + currentStep + ' of {{ count($steps) }}';
        }
    }

    // ─── Form Submission ───
    async function submitForm() {
        if (!validateCurrentStep()) return false;

        const submitBtn = form.querySelector('[type="submit"], .wizard-submit');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Submitting...';
        }

        const formData = new FormData(form);
        // Remove wizard internals
        formData.delete('wizard_step');
        formData.delete('wizard_completed');

        try {
            const response = await fetch(form.action, {
                method: form.method,
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                clearDraft();
                if (completedInput) completedInput.value = 'true';
                if (stepInput) stepInput.value = 'completed';

                const data = await response.json();
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else {
                    // Show success and redirect
                    showToast('success', 'Form submitted successfully!');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                }
            } else {
                const error = await response.json();
                showToast('error', error.message || 'Submission failed');
            }
        } catch (err) {
            console.error('Submit error:', err);
            showToast('error', 'An error occurred. Please try again.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Submit';
            }
        }
    }

    // ─── Draft Management ───
    function loadDraft() {
        const draft = loadDraft();
        if (draft) {
            // Restore form values
            Object.keys(draft).forEach(key => {
                if (key.startsWith('_')) return;
                const input = form.querySelector('[name="' + key + '"]');
                if (input) {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = draft[input.name] === input.value;
                    } else {
                        input.value = draft[key];
                    }
                }
            });

            // Restore step
            if (draft._wizard_step) {
                showStep(draft._wizard_step, false);
            }

            // Show restore notice
            showToast('info', 'Draft restored from ' + new Date(draft._timestamp).toLocaleTimeString());
        }
    }

    function saveDraftManual() {
        saveDraft();
        showToast('info', 'Draft saved');
    }

    function clearDraftAndReset() {
        if (confirm('Clear saved draft and reset form?')) {
            clearDraft();
            form.reset();
            showStep(1);
            showToast('info', 'Draft cleared');
        }
    }

    // ─── Event Listeners ───
    // Navigation buttons
    form?.querySelectorAll('[data-wizard-action="next"]').forEach(btn => {
        btn.addEventListener('click', () => nextStep());
    });

    form?.querySelectorAll('[data-wizard-action="prev"]').forEach(btn => {
        btn.addEventListener('click', () => prevStep());
    });

    form?.querySelectorAll('[data-wizard-action="submit"]').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            submitForm();
        });
    });

    form?.querySelectorAll('[data-wizard-action="save-draft"]').forEach(btn => {
        btn.addEventListener('click', saveDraftManual);
    });

    form?.querySelectorAll('[data-wizard-action="clear-draft"]').forEach(btn => {
        btn.addEventListener('click', clearDraftAndReset);
    });

    // Step click navigation (only backward or next)
    document.querySelectorAll('.progress-step').forEach((stepEl, index) => {
        stepEl.style.cursor = 'pointer';
        stepEl.addEventListener('click', () => {
            const stepNum = index + 1;
            if (stepNum <= currentStep + 1) {
                goToStep(stepNum);
            }
        });
    });

    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;

        if (e.key === 'ArrowRight' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            nextStep();
        } else if (e.key === 'ArrowLeft' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            prevStep();
        } else if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            if (currentStep === {{ count($steps) }}) {
                submitForm();
            } else {
                nextStep();
            }
        }
    });

    // Exit confirmation
    if (confirmExit) {
        window.addEventListener('beforeunload', function(e) {
            const hasChanges = form.querySelectorAll('input, select, textarea').some(el => el.value !== el.defaultValue);
            if (hasChanges) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    }

    // Auto-save draft
    if (saveDraft) {
        form.addEventListener('input', debounce(saveDraft, 1000));
        form.addEventListener('change', saveDraft);
    }

    // Load draft on init
    const draft = loadDraft();
    if (draft && draft._wizard_step) {
        showStep(draft._wizard_step, false);
        showToast('info', 'Draft restored from ' + new Date(draft._timestamp).toLocaleTimeString());
    }

    // Initialize
    updateProgress(currentStep);

    // Global access
    window.FormWizard = window.FormWizard || {};
    window.FormWizard[wizardId] = {
        next: nextStep,
        prev: prevStep,
        goTo: goToStep,
        submit: submitForm,
        saveDraft: saveDraftManual,
        clearDraft: clearDraftAndReset,
        getCurrentStep: () => currentStep,
        validateCurrent: validateCurrentStep
    };

    // Utility functions
    function debounce(fn, delay) {
        let timer;
        return (...args) => {
            clearTimeout(timer);
            timer = setTimeout(() => fn.apply(this, args), delay);
        };
    }

    function showToast(type, message) {
        // Simple toast - replace with your toast system
        const toast = document.createElement('div');
        toast.className = 'toast align-items-center text-white bg-' + (type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info') + ' border-0 position-fixed bottom-0 end-0 m-3';
        toast.setAttribute('role', 'alert');
        toast.innerHTML = '<div class="d-flex"><div class="toast-body">' + message + '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>';
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
        bsToast.show();
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    function showFieldError(input, message) {
        input.classList.add('is-invalid');
        let errorEl = document.getElementById(input.id + '-error');
        if (!errorEl) {
            errorEl = document.createElement('div');
            errorEl.id = input.id + '-error';
            errorEl.className = 'field-error text-danger small mt-1';
            input.parentNode.appendChild(errorEl);
        }
        errorEl.textContent = message;
    }

    function clearFieldError(input) {
        input.classList.remove('is-invalid');
        const errorEl = document.getElementById(input.id + '-error');
        if (errorEl) errorEl.remove();
    }
})();
</script>
@endpush