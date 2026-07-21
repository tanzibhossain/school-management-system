@php
  $isEdit = ($mode ?? 'create') === 'edit';
  $modalId = $isEdit ? 'editModal' . $a->id : 'createModal';
  $action = $isEdit ? route('admin.announcements.update', $a->id) : route('admin.announcements.store');
  $types = ['general','event','holiday','exam','fee','other'];
  $audiences = ['all','teachers','students','parents'];
  $priorities = ['normal','important','urgent'];
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <div class="modal-header"><h5 class="modal-title">{{ $isEdit ? 'Edit announcement' : 'New announcement' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
      <div class="col-12"><label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label>
        <input name="title" class="form-control" value="{{ $isEdit ? $a->title : old('title') }}" required></div>
      <div class="col-12"><label class="form-label">{{ __('Body') }} <span class="text-danger">*</span></label>
        <textarea name="body" rows="4" class="form-control" required>{{ $isEdit ? $a->body : old('body') }}</textarea></div>
      <div class="col-md-4"><label class="form-label">{{ __('Type') }}</label>
        <select name="type" class="form-select">@foreach ($types as $t)<option value="{{ $t }}" @selected(($isEdit ? $a->type : old('type','general'))===$t)>{{ ucfirst($t) }}</option>@endforeach</select></div>
      <div class="col-md-4"><label class="form-label">{{ __('Audience') }}</label>
        <select name="audience" class="form-select">@foreach ($audiences as $au)<option value="{{ $au }}" @selected(($isEdit ? $a->audience : old('audience','all'))===$au)>{{ ucfirst($au) }}</option>@endforeach</select></div>
      <div class="col-md-4"><label class="form-label">{{ __('Priority') }}</label>
        <select name="priority" class="form-select">@foreach ($priorities as $p)<option value="{{ $p }}" @selected(($isEdit ? $a->priority : old('priority','normal'))===$p)>{{ ucfirst($p) }}</option>@endforeach</select></div>
      <div class="col-md-6"><label class="form-label">{{ __('Expire At') }} <span class="text-muted small">(optional)</span></label>
        <input type="datetime-local" name="expire_at" class="form-control" value="{{ $isEdit && $a->expire_at ? $a->expire_at->format('Y-m-d\TH:i') : '' }}"></div>
      @unless ($isEdit)
        <div class="col-md-6"><label class="form-label">{{ __('Schedule For') }} <span class="text-muted small">(optional)</span></label>
          <input type="datetime-local" name="publish_at" class="form-control"></div>
      @endunless
      <div class="col-12 d-flex gap-4">
        <div class="form-check"><input type="hidden" name="is_pinned" value="0"><input class="form-check-input" type="checkbox" name="is_pinned" value="1" id="pin{{ $modalId }}" @checked($isEdit ? $a->is_pinned : false)><label class="form-check-label" for="pin{{ $modalId }}">{{ __('Pin To Top') }}</label></div>
        @unless ($isEdit)
          <div class="form-check"><input class="form-check-input" type="checkbox" name="publish_now" value="1" id="pubnow" checked><label class="form-check-label" for="pubnow">{{ __('Publish Immediately') }}</label></div>
        @endunless
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
  </form>
</div></div></div>
