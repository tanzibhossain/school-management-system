@php
  $isEdit = ($mode ?? 'create') === 'edit';
  $modalId = $isEdit ? 'editModal' . $t->id : 'createModal';
  $action = $isEdit ? route('admin.leave-types.update', $t->id) : route('admin.leave-types.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <div class="modal-header"><h5 class="modal-title">{{ $isEdit ? 'Edit leave type' : 'New leave type' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
      <div class="col-md-7"><label class="form-label">Name <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ $isEdit ? $t->name : old('name') }}" placeholder="e.g. Sick, Casual" required></div>
      <div class="col-md-5"><label class="form-label">Applies to <span class="text-danger">*</span></label>
        <select name="applies_to" class="form-select" required>
          @foreach (['both' => 'Both', 'student' => 'Student', 'staff' => 'Staff'] as $v => $l)
            <option value="{{ $v }}" @selected(($isEdit ? $t->applies_to : old('applies_to', 'both'))===$v)>{{ $l }}</option>
          @endforeach
        </select></div>
      <div class="col-md-5"><label class="form-label">Max days / year</label>
        <input type="number" min="0" name="max_days_per_year" class="form-control" value="{{ $isEdit ? $t->max_days_per_year : old('max_days_per_year') }}"></div>
      <div class="col-md-7 d-flex align-items-end gap-4">
        <div class="form-check"><input type="hidden" name="requires_attachment" value="0"><input class="form-check-input" type="checkbox" name="requires_attachment" value="1" id="att{{ $modalId }}" @checked($isEdit ? $t->requires_attachment : false)><label class="form-check-label" for="att{{ $modalId }}">Attachment</label></div>
        <div class="form-check"><input type="hidden" name="is_paid" value="0"><input class="form-check-input" type="checkbox" name="is_paid" value="1" id="paid{{ $modalId }}" @checked($isEdit ? $t->is_paid : false)><label class="form-check-label" for="paid{{ $modalId }}">Paid</label></div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
  </form>
</div></div></div>
