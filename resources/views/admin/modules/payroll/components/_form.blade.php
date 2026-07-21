@php
  $isEdit = ($mode ?? 'create') === 'edit';
  $modalId = $isEdit ? 'editModal' . $c->id : 'createModal';
  $action = $isEdit ? route('admin.payroll.components.update', $c->id) : route('admin.payroll.components.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <div class="modal-header"><h5 class="modal-title">{{ $isEdit ? 'Edit component' : 'New component' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
      <div class="col-12"><label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ $isEdit ? $c->name : old('name') }}" placeholder="{{ __('e.g. Basic, House rent, PF') }}" required></div>
      <div class="col-md-6"><label class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
        <select name="component_type" class="form-select" required>
          <option value="earning" @selected(($isEdit ? $c->component_type : old('component_type'))==='earning')>{{ __('Earning') }}</option>
          <option value="deduction" @selected(($isEdit ? $c->component_type : old('component_type'))==='deduction')>{{ __('Deduction') }}</option>
        </select></div>
      <div class="col-md-6"><label class="form-label">{{ __('Sort order') }}</label>
        <input type="number" min="0" name="sort_order" class="form-control" value="{{ $isEdit ? $c->sort_order : old('sort_order', 0) }}"></div>
      <div class="col-12"><div class="form-check"><input type="hidden" name="is_default" value="0"><input class="form-check-input" type="checkbox" name="is_default" value="1" id="def{{ $modalId }}" @checked($isEdit ? $c->is_default : false)><label class="form-check-label" for="def{{ $modalId }}">{{ __('Default (applies to new staff)') }}</label></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
  </form>
</div></div></div>
