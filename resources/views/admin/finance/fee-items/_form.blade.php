@php
  $isEdit = ($mode ?? 'create') === 'edit';
  $modalId = $isEdit ? 'editModal' . $it->id : 'createModal';
  $action = $isEdit ? route('admin.fee-items.update', $it->id) : route('admin.fee-items.store');
  $freqs = ['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly', 'one_time' => 'One time'];
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <div class="modal-header"><h5 class="modal-title">{{ $isEdit ? 'Edit fee item' : 'New fee item' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
      <div class="col-md-6"><label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ $isEdit ? $it->name : old('name') }}" required></div>
      <div class="col-md-3"><label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ $isEdit ? $it->amount : old('amount') }}" required></div>
      <div class="col-md-3"><label class="form-label">{{ __('Frequency') }} <span class="text-danger">*</span></label>
        <select name="frequency" class="form-select" required>
          @foreach ($freqs as $v => $l)<option value="{{ $v }}" @selected(($isEdit ? $it->frequency : old('frequency', 'monthly'))===$v)>{{ $l }}</option>@endforeach
        </select></div>

      <div class="col-md-4"><label class="form-label">{{ __('Category') }} <span class="text-danger">*</span></label>
        <select name="category_id" class="form-select" required>
          <option value="">— select —</option>
          @foreach ($categories as $cat)<option value="{{ $cat->id }}" @selected(($isEdit ? $it->category_id : old('category_id'))==$cat->id)>{{ $cat->name }}</option>@endforeach
        </select></div>
      <div class="col-md-4"><label class="form-label">{{ __('Academic year') }} <span class="text-danger">*</span></label>
        <select name="academic_year_id" class="form-select" required>
          <option value="">— select —</option>
          @foreach ($years as $y)<option value="{{ $y->id }}" @selected(($isEdit ? $it->academic_year_id : old('academic_year_id'))==$y->id)>{{ $y->year }}</option>@endforeach
        </select></div>
      <div class="col-md-4"><label class="form-label">{{ __('Class') }} <span class="text-muted small">(blank = all)</span></label>
        <select name="class_id" class="form-select">
          <option value="">{{ __('All classes') }}</option>
          @foreach ($classes as $cl)<option value="{{ $cl->id }}" @selected(($isEdit ? $it->class_id : old('class_id'))==$cl->id)>{{ $cl->name }}</option>@endforeach
        </select></div>

      <div class="col-md-3"><label class="form-label">{{ __('Due day') }} <span class="text-muted small">(1–28)</span></label>
        <input type="number" min="1" max="28" name="due_day" class="form-control" value="{{ $isEdit ? $it->due_day : old('due_day') }}"></div>
      <div class="col-md-9 d-flex align-items-end">
        <div class="form-check"><input type="hidden" name="is_mandatory" value="0"><input class="form-check-input" type="checkbox" name="is_mandatory" value="1" id="mand{{ $isEdit ? $it->id : 'New' }}" @checked($isEdit ? $it->is_mandatory : true)><label class="form-check-label" for="mand{{ $isEdit ? $it->id : 'New' }}">{{ __('Mandatory') }}</label></div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
  </form>
</div></div></div>
