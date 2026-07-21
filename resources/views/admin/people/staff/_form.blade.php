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
          <input name="employment_type" class="form-control" value="{{ $isEdit ? $s->employment_type : old('employment_type') }}" placeholder="e.g. full_time"></div>
        <div class="col-md-4"><label class="form-label">{{ __('Basic Salary') }}</label>
          <input type="number" step="0.01" min="0" name="basic_salary" class="form-control" value="{{ $isEdit ? $s->basic_salary : old('basic_salary') }}"></div>

        <div class="col-md-6"><label class="form-label">{{ __('Teaching Subject') }}</label>
          <select name="subject_id" class="form-select">{!! $selOptions($subjects, $isEdit ? $s->subject_id : old('subject_id')) !!}</select></div>
        <div class="col-md-6"><label class="form-label">{{ __('RFID Number') }}</label>
          <input name="rfid_number" class="form-control" value="{{ $isEdit ? $s->rfid_number : old('rfid_number') }}"></div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
      <button class="btn btn-primary">{{ $isEdit ? 'Save' : 'Hire' }}</button>
    </div>
  </form>
</div></div></div>
