@php
  $isEdit = ($mode ?? 'create') === 'edit';
  $modalId = $isEdit ? 'editModal' . $t->id : 'createModal';
  $action = $isEdit ? route('admin.id-card-templates.update', $t->id) : route('admin.id-card-templates.store');
  $layouts = ['horizontal_classic','horizontal_modern','vertical','dual_stripe','minimal'];
  $current = $isEdit ? ($t->visible_fields ?? []) : ['name','identifier','photo'];
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <div class="modal-header"><h5 class="modal-title">{{ $isEdit ? 'Edit ID template' : 'New ID template' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
      <div class="col-md-6"><label class="form-label">Name <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ $isEdit ? $t->name : old('name') }}" required></div>
      <div class="col-md-3"><label class="form-label">Type <span class="text-danger">*</span></label>
        <select name="type" class="form-select" required>
          <option value="student" @selected(($isEdit ? $t->type : old('type'))==='student')>Student</option>
          <option value="staff" @selected(($isEdit ? $t->type : old('type'))==='staff')>Staff</option>
        </select></div>
      <div class="col-md-3"><label class="form-label">Font</label>
        <select name="font" class="form-select">
          @foreach (['sans','serif','mono'] as $f)<option value="{{ $f }}" @selected(($isEdit ? $t->font : 'sans')===$f)>{{ ucfirst($f) }}</option>@endforeach
        </select></div>
      <div class="col-md-6"><label class="form-label">Layout <span class="text-danger">*</span></label>
        <select name="layout" class="form-select" required>
          @foreach ($layouts as $l)<option value="{{ $l }}" @selected(($isEdit ? $t->layout : old('layout'))===$l)>{{ ucfirst(str_replace('_',' ',$l)) }}</option>@endforeach
        </select></div>
      <div class="col-md-3"><label class="form-label">Background</label>
        <input type="color" name="background_color" class="form-control form-control-color" value="{{ $isEdit ? $t->background_color : '#ffffff' }}"></div>
      <div class="col-md-3"><label class="form-label">Accent</label>
        <input type="color" name="accent_color" class="form-control form-control-color" value="{{ $isEdit ? $t->accent_color : '#1a56db' }}"></div>
      <div class="col-12"><label class="form-label">Visible fields</label>
        <div class="d-flex flex-wrap gap-3">
          @foreach ($fields as $f)
            <div class="form-check"><input class="form-check-input" type="checkbox" name="visible_fields[]" value="{{ $f }}" id="vf{{ $modalId }}{{ $f }}" @checked(in_array($f, $current))><label class="form-check-label text-capitalize" for="vf{{ $modalId }}{{ $f }}">{{ str_replace('_',' ',$f) }}</label></div>
          @endforeach
        </div></div>
      <div class="col-12"><div class="form-check"><input type="hidden" name="is_default" value="0"><input class="form-check-input" type="checkbox" name="is_default" value="1" id="def{{ $modalId }}" @checked($isEdit ? $t->is_default : false)><label class="form-check-label" for="def{{ $modalId }}">Default for this type</label></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
  </form>
</div></div></div>
