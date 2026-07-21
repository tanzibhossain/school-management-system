@extends('layouts.admin')
@section('title', 'Submissions — ' . $assignment->title)
@section('content')
  @include('admin.partials.page-header', [
    'title'  => $assignment->title,
    'crumbs' => ['LMS', 'Courses', $assignment->course?->title, 'Submissions'],
  ])
  <div class="mb-3"><a href="{{ route('admin.lms.courses.show', $assignment->course_id) }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> {{ __('Back to course') }}</a></div>

  <div class="card mb-3"><div class="card-body d-flex justify-content-between">
    <div><span class="text-muted">Max marks:</span> <strong>{{ $assignment->max_marks }}</strong></div>
    <div><span class="text-muted">Submissions:</span> <strong>{{ $submissions->count() }}</strong></div>
  </div></div>

  <div class="card"><div class="card-body">
    @if ($submissions->isEmpty())
      <p class="text-muted mb-0">{{ __('No submissions yet.') }}</p>
    @else
      <table class="table align-middle mb-0">
        <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Submitted') }}</th><th>{{ __('Late') }}</th><th>{{ __('AI check') }}</th><th class="text-end">{{ __('Marks') }}</th><th class="text-end" data-orderable="false">{{ __('Grade') }}</th></tr></thead>
        <tbody>
          @foreach ($submissions as $s)
            <tr>
              <td class="fw-semibold">{{ $s->student?->name ?? '—' }}</td>
              <td class="small">{{ optional($s->submitted_at)->format('d M Y H:i') }}</td>
              <td>@if ($s->late_submission)<span class="badge text-bg-warning">{{ __('Late') }}</span>@else — @endif</td>
              <td>
                @if ($s->aiCheck)
                  <span class="badge text-bg-{{ $s->aiCheck->likely_ai_generated ? 'danger' : 'success' }}">{{ $s->aiCheck->likely_ai_generated ? 'Likely AI' : 'OK' }}{{ $s->aiCheck->ai_score !== null ? ' (' . $s->aiCheck->ai_score . ')' : '' }}</span>
                @else — @endif
              </td>
              <td class="text-end">{{ $s->marks_awarded ?? '—' }}{{ $s->graded_at ? '' : '' }}</td>
              <td class="text-end">
                <form method="POST" action="{{ route('admin.lms.submissions.grade', $s->id) }}" class="d-inline-flex gap-1">
                  @csrf @method('PATCH')
                  <input type="number" min="0" max="{{ $assignment->max_marks }}" name="marks_awarded" class="form-control form-control-sm" style="width:5rem" value="{{ $s->marks_awarded }}" required>
                  <input name="feedback" class="form-control form-control-sm" style="width:10rem" placeholder="{{ __('Feedback') }}" value="{{ $s->teacher_feedback }}">
                  <button class="btn btn-sm btn-primary">{{ __('Save') }}</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div></div>
@endsection
