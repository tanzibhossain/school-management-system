@extends('layouts.admin')
@section('title', __('LMS — courses'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Courses',
    'crumbs' => ['LMS', 'Courses'],
    'action' => ['label' => 'New course', 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Title') }}</th><th>{{ __('Class') }}</th><th>{{ __('Subject') }}</th><th>{{ __('Teacher') }}</th><th>{{ __('Lessons') }}</th><th>{{ __('Assignments') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($courses as $c)
          <tr>
            <td class="fw-semibold">{{ $c->title }}</td>
            <td>{{ $c->schoolClass?->name ?? '—' }}</td>
            <td>{{ $c->subject?->name ?? '—' }}</td>
            <td>{{ $c->teacher?->name ?? '—' }}</td>
            <td>{{ $c->lessons_count }}</td>
            <td>{{ $c->assignments_count }}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.lms.courses.show', $c->id) }}">{{ __('Open') }}</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.lms.courses.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('New course') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label>
          <input name="title" class="form-control" value="{{ old('title') }}" required></div>
        <div class="col-md-6"><label class="form-label">{{ __('Class') }} <span class="text-danger">*</span></label>
          <select name="class_id" class="form-select" required><option value="">— select —</option>
            @foreach ($classes as $cl)<option value="{{ $cl->id }}" @selected(old('class_id')==$cl->id)>{{ $cl->name }}</option>@endforeach
          </select></div>
        <div class="col-md-6"><label class="form-label">{{ __('Subject') }} <span class="text-danger">*</span></label>
          <select name="subject_id" class="form-select js-select" required><option value="">— select —</option>
            @foreach ($subjects as $s)<option value="{{ $s->id }}" @selected(old('subject_id')==$s->id)>{{ $s->name }}</option>@endforeach
          </select></div>
        <div class="col-12"><label class="form-label">{{ __('Teacher') }}</label>
          <select name="teacher_id" class="form-select js-select"><option value="">— none —</option>
            @foreach ($teachers as $t)<option value="{{ $t->id }}" @selected(old('teacher_id')==$t->id)>{{ $t->name }}</option>@endforeach
          </select></div>
        <div class="col-12"><label class="form-label">{{ __('Description') }}</label>
          <textarea name="description" rows="2" class="form-control">{{ old('description') }}</textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Create') }}</button></div>
    </form>
  </div></div></div>
@endsection
