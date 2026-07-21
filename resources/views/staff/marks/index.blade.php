@extends('layouts.staff')
@section('title', __('Marks'))
@section('heading', 'Marks & Results')
@section('content')

  <div class="mb-3">
    <p class="text-muted small mb-0">Enter marks for your subject{{ $staff?->subject ? ' — ' . e($staff->subject->name) : '' }}. Results are calculated and locked by the exam office.</p>
  </div>

  @if(! $staff?->subject_id)
    <div class="alert alert-info"><i class="bi bi-info-circle me-1"></i> {{ __('You Do Not Have A Teaching Subject Assigned. Ask The Administrator To Set Your Subject.') }}</div>
  @elseif($grouped->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
      <i class="bi bi-journal-text fs-3 d-block mb-2 opacity-50"></i>No mark divisions have been set up for your subject yet.
    </div></div>
  @else
    @foreach($grouped as $examId => $divisions)
      @php $exam = $exams[$examId] ?? null; @endphp
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>{{ $exam?->title ?? 'Exam' }} <span class="text-muted small">· {{ $exam?->schoolClass->name ?? '' }} · {{ $exam?->examType->name ?? '' }}</span></span>
          <span class="badge text-bg-light">{{ ucfirst($exam?->status ?? '') }}</span>
        </div>
        <div class="card-body p-0">
          <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>{{ __('Division') }}</th><th class="text-end">{{ __('Max') }}</th><th class="text-end">{{ __('Pass') }}</th><th class="text-end">{{ __('Action') }}</th></tr></thead>
            <tbody>
              @foreach($divisions as $d)
                <tr>
                  <td class="fw-medium">{{ $d->examSubject->subjectRelation->subject->name ?? '' }} — {{ $d->name }}</td>
                  <td class="text-end">{{ rtrim(rtrim(number_format($d->max_marks, 2), '0'), '.') }}</td>
                  <td class="text-end">{{ $d->pass_mark !== null ? rtrim(rtrim(number_format($d->pass_mark, 2), '0'), '.') : '—' }}</td>
                  <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('staff.marks.entry', [$examId, $d->id]) }}">{{ __('Enter Marks') }}</a></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    @endforeach
  @endif

@endsection
