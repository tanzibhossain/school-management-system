@php
  $isEdit = ($mode ?? 'create') === 'edit';
  $modalId = $isEdit ? 'editModal' . $m->id : 'createModal';
  $action = $isEdit ? route('admin.library.members.update', $m->id) : route('admin.library.members.store');
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="POST" action="{{ $action }}">
    @csrf
    @if ($isEdit) @method('PUT') @endif
    <div class="modal-header"><h5 class="modal-title">{{ $isEdit ? 'Edit member' : 'Add member' }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
      <div class="col-12"><label class="form-label">{{ __('User account') }} <span class="text-danger">*</span></label>
        <select name="user_id" class="form-select js-select" required>
          <option value="">— select —</option>
          @foreach ($users as $usr)<option value="{{ $usr->id }}" @selected(($isEdit ? $m->user_id : old('user_id'))==$usr->id)>{{ $usr->name }}</option>@endforeach
        </select></div>
      <div class="col-md-7"><label class="form-label">{{ __('Membership number') }} <span class="text-danger">*</span></label>
        <input name="membership_number" class="form-control" value="{{ $isEdit ? $m->membership_number : old('membership_number') }}" required></div>
      <div class="col-md-5"><label class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
        <select name="member_type" class="form-select" required>
          <option value="student" @selected(($isEdit ? $m->member_type : old('member_type'))==='student')>{{ __('Student') }}</option>
          <option value="staff" @selected(($isEdit ? $m->member_type : old('member_type'))==='staff')>{{ __('Staff') }}</option>
        </select></div>
      <div class="col-md-6"><label class="form-label">{{ __('Joined') }}</label>
        <input type="date" name="joined_at" class="form-control" value="{{ $isEdit ? optional($m->joined_at)->format('Y-m-d') : old('joined_at') }}"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
  </form>
</div></div></div>
