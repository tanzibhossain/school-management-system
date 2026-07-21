@extends('layouts.admin')
@section('title', $course->title)
@section('content')
  @include('admin.partials.page-header', [
    'title'  => $course->title,
    'crumbs' => ['LMS', 'Courses', $course->title],
  ])
  <div class="mb-3 d-flex justify-content-between">
    <a href="{{ route('admin.lms.courses.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> {{ __('Back To Courses') }}</a>
    <span class="text-muted small">{{ $course->schoolClass?->name }} · {{ $course->subject?->name }} · {{ $course->teacher?->name ?? 'no teacher' }}</span>
  </div>

  <div class="row g-4">
    {{-- Lessons --}}
    <div class="col-lg-6">
      <div class="card"><div class="card-header d-flex justify-content-between align-items-center">
        <span>Lessons ({{ $course->lessons->count() }})</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#lessonModal"><i class="bi bi-plus-lg"></i> {{ __('Add Lesson') }}</button>
      </div><div class="card-body">
        @if ($course->lessons->isEmpty())
          <p class="text-muted mb-0">{{ __('No Lessons Yet.') }}</p>
        @else
          <ul class="list-group list-group-flush">
            @foreach ($course->lessons as $l)
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span>
                  <span class="badge text-bg-light border text-muted me-1">{{ $l->sort_order }}</span>
                  {{ $l->title }}
                  <span class="badge text-bg-{{ $l->content_type === 'video' ? 'info' : 'secondary' }}">{{ $l->content_type }}</span>
                  @if ($l->is_published)<span class="badge text-bg-success">{{ __('Published') }}</span>@else<span class="badge text-bg-warning">{{ __('Draft') }}</span>@endif
                </span>
                <span class="d-flex gap-1">
                  @unless ($l->is_published)
                    <form method="POST" action="{{ route('admin.lms.courses.lessons.publish', [$course->id, $l->id]) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">{{ __('Publish') }}</button></form>
                  @endunless
                  <form method="POST" action="{{ route('admin.lms.courses.lessons.destroy', [$course->id, $l->id]) }}" onsubmit="return confirm('Remove lesson?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">✕</button></form>
                </span>
              </li>
            @endforeach
          </ul>
        @endif
      </div></div>
    </div>

    {{-- Assignments --}}
    <div class="col-lg-6">
      <div class="card"><div class="card-header d-flex justify-content-between align-items-center">
        <span>Assignments ({{ $course->assignments->count() }})</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignmentModal"><i class="bi bi-plus-lg"></i> {{ __('Add Assignment') }}</button>
      </div><div class="card-body">
        @if ($course->assignments->isEmpty())
          <p class="text-muted mb-0">{{ __('No Assignments Yet.') }}</p>
        @else
          <ul class="list-group list-group-flush">
            @foreach ($course->assignments as $a)
              <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                <span>{{ $a->title }} <span class="text-muted small">· max {{ $a->max_marks }}@if ($a->due_date) · due {{ $a->due_date->format('d M Y') }}@endif</span></span>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.lms.courses.assignments.show', [$course->id, $a->id]) }}">{{ __('Submissions') }}</a>
              </li>
            @endforeach
          </ul>
        @endif
      </div></div>
    </div>
  </div>

  {{-- Lesson modal --}}
  <div class="modal fade" id="lessonModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.lms.courses.lessons.store', $course->id) }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('Add Lesson') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-md-8"><label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label>
          <input name="title" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">{{ __('Type') }}</label>
          <select name="content_type" id="lessonType" class="form-select">
            <option value="text">{{ __('Text') }}</option><option value="video">{{ __('Video') }}</option>
          </select></div>
        <div class="col-12 lesson-text"><label class="form-label">{{ __('Content') }}</label>
          <textarea name="body_text" rows="3" class="form-control"></textarea></div>
        <div class="col-12 lesson-video d-none"><label class="form-label">{{ __('Video URL') }}</label>
          <input name="video_url" class="form-control" placeholder="{{ __('https://...') }}"></div>
        <div class="col-md-4"><label class="form-label">{{ __('Sort Order') }}</label>
          <input type="number" min="0" name="sort_order" class="form-control" value="0"></div>
        <div class="col-md-8 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_published" value="1" id="lpub"><label class="form-check-label" for="lpub">{{ __('Publish Now') }}</label></div></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Add') }}</button></div>
    </form>
  </div></div></div>

  {{-- Assignment modal --}}
  <div class="modal fade" id="assignmentModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.lms.courses.assignments.store', $course->id) }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('Add Assignment') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-md-8"><label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label>
          <input name="title" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">{{ __('Max Marks') }} <span class="text-danger">*</span></label>
          <input type="number" min="1" name="max_marks" class="form-control" value="100" required></div>
        <div class="col-md-6"><label class="form-label">{{ __('Due Date') }} <span class="text-danger">*</span></label>
          <input type="datetime-local" name="due_date" class="form-control" required></div>
        <div class="col-md-6 d-flex align-items-end"><div class="form-check"><input class="form-check-input" type="checkbox" name="allow_late_submission" value="1" id="late"><label class="form-check-label" for="late">{{ __('Allow Late Submissions') }}</label></div></div>
        <div class="col-12"><label class="form-label">{{ __('Instructions') }}</label><textarea name="instructions" rows="2" class="form-control"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Create') }}</button></div>
    </form>
  </div></div></div>

  @push('scripts')
    <script>
      var lt = document.getElementById('lessonType');
      lt.addEventListener('change', function () {
        document.querySelector('.lesson-text').classList.toggle('d-none', lt.value !== 'text');
        document.querySelector('.lesson-video').classList.toggle('d-none', lt.value !== 'video');
      });
    </script>
  @endpush
@endsection
