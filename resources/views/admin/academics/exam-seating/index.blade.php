@extends('layouts.admin')
@section('title', 'Seating · ' . $exam->title)
@section('content')
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">{{ __('Home') }}</a></li><li class="breadcrumb-item">{{ __('Academics') }}</li><li class="breadcrumb-item"><a href="{{ route('admin.exams.index') }}" class="text-decoration-none">{{ __('Exams') }}</a></li><li class="breadcrumb-item"><a href="{{ route('admin.exams.show', $exam->id) }}" class="text-decoration-none">{{ $exam->title }}</a></li><li class="breadcrumb-item active">{{ __('Seating') }}</li></ol></nav>
      <h1 class="h4 mb-0">Seating — {{ $exam->title }}</h1>
      <div class="text-muted small mt-1">{{ $exam->schoolClass?->name }}</div>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal"><i class="bi bi-grid-3x3-gap"></i> {{ __('Assign Seats') }}</button>
      @if ($seating->isNotEmpty())
        <form method="POST" action="{{ route('admin.exam-seating.clear', $exam->id) }}" onsubmit="return confirm('Clear all seating for this exam?')">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-x-lg"></i> {{ __('Clear') }}</button></form>
      @endif
    </div>
  </div>

  <div class="card"><div class="card-body">
    @if ($seating->isEmpty())
      <p class="text-muted mb-0">{{ __('No Seating Assigned Yet.') }}</p>
    @else
      <table class="table table-hover align-middle w-100 js-dt">
        <thead><tr><th>{{ __('Hall') }}</th><th>{{ __('Seat') }}</th><th>{{ __('Roll') }}</th><th>{{ __('Student') }}</th></tr></thead>
        <tbody>
          @foreach ($seating as $s)
            <tr>
              <td>{{ $s->hallSeat?->hall?->name ?? '—' }}</td>
              <td><code>{{ $s->hallSeat?->label ?? '—' }}</code></td>
              <td>{{ $s->exam_roll ?? '—' }}</td>
              <td class="fw-semibold">{{ $s->student?->name ?? '—' }} <span class="text-muted small">({{ $s->student?->student_id }})</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div></div>

  <div class="modal fade" id="assignModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.exam-seating.assign', $exam->id) }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('Assign Seats') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">{{ __('Hall') }} <span class="text-danger">*</span></label>
          <select name="hall_id" class="form-select" required>
            <option value="">— select —</option>
            @foreach ($halls as $h)<option value="{{ $h->id }}">{{ $h->name }} ({{ $h->available_count }} seats)</option>@endforeach
          </select>
          @if ($halls->isEmpty())<div class="form-text text-danger">No halls yet — create one under Academics → Exam halls.</div>@endif
        </div>
        <div class="col-md-7"><label class="form-label">{{ __('Strategy') }}</label>
          <select name="strategy" class="form-select">
            <option value="">Exam default ({{ $exam->seating_strategy }})</option>
            <option value="sequential">{{ __('Sequential') }}</option>
            <option value="interleave_section">{{ __('Interleave Section') }}</option>
            <option value="interleave_group">{{ __('Interleave Group') }}</option>
            <option value="anti_adjacency">{{ __('Anti-adjacency') }}</option>
          </select></div>
        <div class="col-md-5"><label class="form-label">{{ __('Blank Every') }}</label>
          <input type="number" min="1" max="10" name="blank_every" class="form-control" placeholder="{{ __('None') }}">
          <div class="form-text">{{ __('Leave A Gap Seat After Every N Seats.') }}</div></div>
        <div class="col-12"><div class="alert alert-warning py-2 mb-0 small">{{ __('Re-assigning Replaces Any Existing Seating For This Exam.') }}</div></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Assign') }}</button></div>
    </form>
  </div></div></div>
@endsection
