@extends('layouts.admin')
@section('title', 'Marks · ' . $exam->title)
@section('content')
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">{{ __('Home') }}</a></li><li class="breadcrumb-item">{{ __('Academics') }}</li><li class="breadcrumb-item"><a href="{{ route('admin.exams.index') }}" class="text-decoration-none">{{ __('Exams') }}</a></li><li class="breadcrumb-item"><a href="{{ route('admin.exams.show', $exam->id) }}" class="text-decoration-none">{{ $exam->title }}</a></li><li class="breadcrumb-item active">{{ __('Marks') }}</li></ol></nav>
      <h1 class="h4 mb-0">Marks — {{ $exam->title }}</h1>
      <div class="text-muted small mt-1">{{ $exam->schoolClass?->name }}</div>
    </div>
    <div class="d-flex gap-2">
      <form method="POST" action="{{ route('admin.exam-marks.calculate', $exam->id) }}" onsubmit="return confirm('Calculate results for this exam?')">@csrf<button class="btn btn-primary" {{ $locked ? 'disabled' : '' }}><i class="bi bi-calculator"></i> {{ __('Calculate Results') }}</button></form>
      @if ($resultCount > 0)
        <a class="btn btn-outline-secondary" href="{{ route('admin.exam-marks.results', $exam->id) }}"><i class="bi bi-table"></i> {{ __('Tabulation') }}</a>
        @unless ($locked)
          <form method="POST" action="{{ route('admin.exam-marks.lock', $exam->id) }}" onsubmit="return confirm('Lock results? Marks can no longer be changed.')">@csrf @method('PATCH')<button class="btn btn-outline-danger"><i class="bi bi-lock"></i> {{ __('Lock') }}</button></form>
        @endunless
      @endif
    </div>
  </div>

  @if ($locked)<div class="alert alert-danger"><i class="bi bi-lock-fill"></i> {{ __('Results Are Locked — Marks Cannot Be Changed.') }}</div>@endif

  @if ($exam->subjects->isEmpty())
    <div class="alert alert-warning">{{ __('This Exam Has No Subjects. Add Subjects On The') }} <a href="{{ route('admin.exams.show', $exam->id) }}">{{ __('Exam Page') }}</a> {{ __('First.') }}</div>
  @else
    @foreach ($exam->subjects as $subject)
      @php $divs = $divisions[$subject->id] ?? collect(); @endphp
      <div class="card mb-3"><div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold">{{ $subject->subjectRelation?->subject?->name ?? 'Subject #' . $subject->id }} <span class="text-muted small">(full {{ $subject->full_marks }}, pass {{ $subject->pass_marks }})</span></span>
        @unless ($locked)<button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#divModal{{ $subject->id }}"><i class="bi bi-plus-lg"></i> {{ __('Add Division') }}</button>@endunless
      </div><div class="card-body">
        @if ($divs->isEmpty())
          <p class="text-muted mb-0">{{ __('No Divisions Yet — Add One (E.g. Written / MCQ) To Enter Marks.') }}</p>
        @else
          <table class="table align-middle mb-0">
            <thead><tr><th>{{ __('Division') }}</th><th class="text-end">{{ __('Max') }}</th><th class="text-end">{{ __('Pass') }}</th><th class="text-end" data-orderable="false"></th></tr></thead>
            <tbody>
              @foreach ($divs->sortBy('display_order') as $d)
                <tr>
                  <td class="fw-semibold">{{ $d->name }}</td>
                  <td class="text-end">{{ $d->max_marks }}</td>
                  <td class="text-end">{{ $d->pass_mark ?? '—' }}</td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-primary" href="{{ route('admin.exam-marks.entry', [$exam->id, $d->id]) }}">{{ __('Enter Marks') }}</a>
                    @unless ($locked)
                      <form method="POST" action="{{ route('admin.exam-marks.divisions.destroy', [$exam->id, $d->id]) }}" class="d-inline" onsubmit="return confirm('Remove division {{ $d->name }}?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">{{ __('Remove') }}</button>
                      </form>
                    @endunless
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div></div>

      @unless ($locked)
        <div class="modal fade" id="divModal{{ $subject->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
          <form method="POST" action="{{ route('admin.exam-marks.divisions.store', $exam->id) }}">
            @csrf
            <input type="hidden" name="exam_subject_id" value="{{ $subject->id }}">
            <div class="modal-header"><h5 class="modal-title">{{ __('Add Division') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
              <div class="col-12"><label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
                <input name="name" class="form-control" placeholder="{{ __('E.g. Written') }}" required></div>
              <div class="col-md-6"><label class="form-label">{{ __('Max Marks') }} <span class="text-danger">*</span></label>
                <input type="number" step="0.01" min="1" name="max_marks" class="form-control" required></div>
              <div class="col-md-6"><label class="form-label">{{ __('Pass Mark') }}</label>
                <input type="number" step="0.01" min="0" name="pass_mark" class="form-control"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Add') }}</button></div>
          </form>
        </div></div></div>
      @endunless
    @endforeach
  @endif
@endsection
