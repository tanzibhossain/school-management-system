@php
  $isEdit = ($mode ?? 'create') === 'edit';
  $modalId = $isEdit ? 'editModal' . $v->id : 'createModal';
  $action = $isEdit ? route('admin.transport.vehicles.update', $v->id) : route('admin.transport.vehicles.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <div class="modal-header"><h5 class="modal-title">{{ $isEdit ? 'Edit vehicle' : 'Add vehicle' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
      <div class="col-md-7"><label class="form-label">{{ __('Registration Number') }} <span class="text-danger">*</span></label>
        <input name="registration_no" class="form-control" value="{{ $isEdit ? $v->registration_no : old('registration_no') }}" required></div>
      <div class="col-md-5"><label class="form-label">{{ __('Capacity') }} <span class="text-danger">*</span></label>
        <input type="number" min="1" name="capacity" class="form-control" value="{{ $isEdit ? $v->capacity : old('capacity') }}" required></div>
      <div class="col-12"><label class="form-label">{{ __('Notes') }}</label>
        <input name="notes" class="form-control" value="{{ $isEdit ? $v->notes : old('notes') }}"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
  </form>
</div></div></div>
