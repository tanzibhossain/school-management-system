@php
  $isEdit = ($mode ?? 'create') === 'edit';
  $modalId = $isEdit ? 'editModal' . $s->id : 'createModal';
  $action = $isEdit ? route('admin.staff.update', $s->id) : route('admin.staff.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <div class="modal-header">
      <h5 class="modal-title">{{ $isEdit ? 'Edit staff' : 'Hire staff' }}</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
          <input name="name" class="form-control" value="{{ $isEdit ? $s->name : old('name') }}" required></div>
        <div class="col-md-3"><label class="form-label">{{ __('Gender') }}</label>
          <select name="gender" class="form-select">{!! $genderOptions($isEdit ? $s->gender : old('gender')) !!}</select></div>
        <div class="col-md-3"><label class="form-label">{{ __('Date Of Birth') }}</label>
          <input type="date" name="dob" class="form-control" value="{{ $isEdit ? optional($s->dob)->format('Y-m-d') : old('dob') }}"></div>

        <div class="col-md-6"><label class="form-label">{{ __('Designation') }}</label>
          <select name="designation_id" class="form-select">{!! $selOptions($designations, $isEdit ? $s->designation_id : old('designation_id')) !!}</select></div>
        <div class="col-md-6"><label class="form-label">{{ __('Department') }}</label>
          <select name="department_id" class="form-select">{!! $selOptions($departments, $isEdit ? $s->department_id : old('department_id')) !!}</select></div>

        <div class="col-md-4"><label class="form-label">{{ __('Joining Date') }}</label>
          <input type="date" name="joining_date" class="form-control" value="{{ $isEdit ? optional($s->joining_date)->format('Y-m-d') : old('joining_date') }}"></div>
        <div class="col-md-4"><label class="form-label">{{ __('Employment Type') }}</label>
          @php $empType = $isEdit ? $s->employment_type : old('employment_type', 'permanent'); @endphp
          <select name="employment_type" class="form-select">
            @foreach (\App\Modules\Staff\Models\Staff::EMPLOYMENT_TYPES as $type)
              <option value="{{ $type }}" @selected($empType === $type)>{{ __(ucfirst(str_replace('_', ' ', $type))) }}</option>
            @endforeach
          </select></div>
        <div class="col-md-4"><label class="form-label">{{ __('Basic Salary') }}</label>
          <input type="number" step="0.01" min="0" name="basic_salary" class="form-control" value="{{ $isEdit ? $s->basic_salary : old('basic_salary') }}"></div>

        <div class="col-md-6"><label class="form-label">{{ __('Teaching Subject') }}</label>
          <select name="subject_id" class="form-select">{!! $selOptions($subjects, $isEdit ? $s->subject_id : old('subject_id')) !!}</select></div>
        <div class="col-md-6"><label class="form-label">{{ __('RFID Number') }}</label>
          <input name="rfid_number" class="form-control" value="{{ $isEdit ? $s->rfid_number : old('rfid_number') }}"></div>

        {{-- Translations — docs/modules/30-multilingual-content-plan.md Phase
             4/5. Only offered once the staff member exists (hiring has no id
             yet to attach translation rows to). --}}
        @if ($isEdit && isset($contentLanguages) && $contentLanguages->isNotEmpty())
          <div class="col-12">
            <hr class="my-2">
            <p class="fw-semibold small mb-2">{{ __('Translations') }}</p>
            <p class="text-muted small mb-3">{{ __('Leave a field blank to fall back to the default-language content above.') }}</p>
            @foreach ($contentLanguages as $lang)
              @php
                $t = old('translations.'.$lang->code, ['name' => $s->trans('name', $lang->code)]);
              @endphp
              <details class="card mb-2">
                <summary class="card-header py-1" style="cursor:pointer;">
                  @if ($lang->flag){{ $lang->flag }} @endif {{ $lang->native_name }}
                </summary>
                <div class="card-body">
                  <button type="button" class="btn btn-sm btn-outline-secondary mb-2 js-suggest-translation"
                          data-form="ai-suggest-staff-{{ $s->id }}-{{ $lang->code }}">
                    <i class="bi bi-magic"></i> {{ __('Suggest translations (AI)') }}
                  </button>
                  <p class="form-text mt-0 mb-3">{{ __('Fills only the empty fields below using a free machine-translation service — always review a suggestion before saving.') }}</p>
                  <label class="form-label">{{ __('Name') }}</label>
                  <input name="translations[{{ $lang->code }}][name]" class="form-control"
                      value="{{ $t['name'] }}" placeholder="{{ $s->name }}">
                </div>
              </details>
            @endforeach
          </div>
        @endif
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
      <button class="btn btn-primary">{{ $isEdit ? 'Save' : 'Hire' }}</button>
    </div>
  </form>
</div></div></div>

{{-- One tiny standalone form per language, submitted via JS from the
     "Suggest translations (AI)" button inside each language panel above —
     kept OUTSIDE this row's own <form> since HTML forms can't nest.
     docs/modules/30-multilingual-content-plan.md Phase 5. --}}
@if ($isEdit && isset($contentLanguages))
  @foreach ($contentLanguages as $lang)
    <form method="POST" action="{{ route('admin.staff.translations.suggest', $s->id) }}" id="ai-suggest-staff-{{ $s->id }}-{{ $lang->code }}" class="d-none">
      @csrf
      <input type="hidden" name="locale" value="{{ $lang->code }}">
    </form>
  @endforeach
@endif
