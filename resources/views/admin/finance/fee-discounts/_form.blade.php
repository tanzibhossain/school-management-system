@php
  $isEdit = ($mode ?? 'create') === 'edit';
  $modalId = $isEdit ? 'editModal' . $d->id : 'createModal';
  $action = $isEdit ? route('admin.fee-discounts.update', $d->id) : route('admin.fee-discounts.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <div class="modal-header"><h5 class="modal-title">{{ $isEdit ? 'Edit discount' : 'New discount' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
      <div class="col-12"><label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ $isEdit ? $d->name : old('name') }}" required></div>
      <div class="col-md-5"><label class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
        <select name="type" class="form-select" required>
          <option value="percentage" @selected(($isEdit ? $d->type : old('type'))==='percentage')>{{ __('Percentage') }}</option>
          <option value="fixed" @selected(($isEdit ? $d->type : old('type'))==='fixed')>{{ __('Fixed Amount') }}</option>
        </select></div>
      <div class="col-md-3"><label class="form-label">{{ __('Value') }} <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0" name="value" class="form-control" value="{{ $isEdit ? $d->value : old('value') }}" required></div>
      <div class="col-md-4"><label class="form-label">{{ __('Max Amount') }}</label>
        <input type="number" step="0.01" min="0" name="max_amount" class="form-control" value="{{ $isEdit ? $d->max_amount : old('max_amount') }}"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
  </form>
</div></div></div>
