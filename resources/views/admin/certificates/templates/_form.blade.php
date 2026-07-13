@php
  $isEdit = ($mode ?? 'create') === 'edit';
  $modalId = $isEdit ? 'editModal' . $t->id : 'createModal';
  $action = $isEdit ? route('admin.cert-templates.update', $t->id) : route('admin.cert-templates.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <div class="modal-header"><h5 class="modal-title">{{ $isEdit ? 'Edit template' : 'New template' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
      <div class="col-12"><label class="form-label">Name <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ $isEdit ? $t->name : old('name') }}" required></div>
      <div class="col-12"><label class="form-label">Body <span class="text-danger">*</span></label>
        <textarea name="template_body" rows="5" class="form-control" required>{{ $isEdit ? $t->template_body : old('template_body') }}</textarea>
        <div class="form-text">Supports placeholders like <code>@{{ student_name }}</code>, <code>@{{ conduct_remark }}</code>.</div></div>
      <div class="col-md-6"><label class="form-label">Signatory name</label>
        <input name="signatory_name" class="form-control" value="{{ $isEdit ? $t->signatory_name : old('signatory_name') }}"></div>
      <div class="col-md-6"><label class="form-label">Signatory designation</label>
        <input name="signatory_designation" class="form-control" value="{{ $isEdit ? $t->signatory_designation : old('signatory_designation') }}"></div>
      <div class="col-12"><label class="form-label">Footer text</label>
        <input name="footer_text" class="form-control" value="{{ $isEdit ? $t->footer_text : old('footer_text') }}"></div>
      <div class="col-12"><div class="form-check"><input type="hidden" name="is_default" value="0"><input class="form-check-input" type="checkbox" name="is_default" value="1" id="def{{ $modalId }}" @checked($isEdit ? $t->is_default : false)><label class="form-check-label" for="def{{ $modalId }}">Default template</label></div></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
  </form>
</div></div></div>
