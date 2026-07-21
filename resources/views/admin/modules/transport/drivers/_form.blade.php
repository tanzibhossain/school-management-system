@php
  $isEdit = ($mode ?? 'create') === 'edit';
  $modalId = $isEdit ? 'editModal' . $d->id : 'createModal';
  $action = $isEdit ? route('admin.transport.drivers.update', $d->id) : route('admin.transport.drivers.store');
  $statuses = ['active' => 'Active', 'on_leave' => 'On leave', 'inactive' => 'Inactive'];
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <div class="modal-header"><h5 class="modal-title">{{ $isEdit ? 'Edit driver' : 'Add driver' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
      <div class="col-12"><label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ $isEdit ? $d->name : old('name') }}" required></div>
      <div class="col-md-6"><label class="form-label">{{ __('Phone') }}</label>
        <input name="phone" class="form-control" value="{{ $isEdit ? $d->phone : old('phone') }}"></div>
      <div class="col-md-6"><label class="form-label">{{ __('License number') }}</label>
        <input name="license_no" class="form-control" value="{{ $isEdit ? $d->license_no : old('license_no') }}"></div>
      <div class="col-md-6"><label class="form-label">{{ __('Status') }}</label>
        <select name="status" class="form-select">
          @foreach ($statuses as $v => $l)<option value="{{ $v }}" @selected(($isEdit ? $d->status : old('status','active'))===$v)>{{ $l }}</option>@endforeach
        </select></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
  </form>
</div></div></div>
