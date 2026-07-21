@extends('layouts.admin')
@section('title', 'Results · ' . $exam->title)
@section('content')
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">{{ __('Home') }}</a></li><li class="breadcrumb-item">{{ __('Academics') }}</li><li class="breadcrumb-item"><a href="{{ route('admin.exams.index') }}" class="text-decoration-none">{{ __('Exams') }}</a></li><li class="breadcrumb-item"><a href="{{ route('admin.exam-marks.index', $exam->id) }}" class="text-decoration-none">{{ $exam->title }}</a></li><li class="breadcrumb-item active">{{ __('Results') }}</li></ol></nav>
      <h1 class="h4 mb-0">Tabulation — {{ $exam->title }}
        @if ($locked)<span class="badge text-bg-danger align-middle"><i class="bi bi-lock-fill"></i> {{ __('Locked') }}</span>@endif
      </h1>
      <div class="text-muted small mt-1">{{ $exam->schoolClass?->name }} · {{ $exam->examType?->name }}</div>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.exam-marks.index', $exam->id) }}"><i class="bi bi-arrow-left"></i> {{ __('Back To Marks') }}</a>
  </div>

  <div class="card"><div class="card-body">
    @if ($rows->isEmpty())
      <p class="text-muted mb-0">{{ __('No Results Yet — Calculate Results First.') }}</p>
    @else
      <table class="table table-hover align-middle w-100 js-dt">
        <thead><tr><th>{{ __('Merit') }}</th><th>{{ __('Student') }}</th><th class="text-end">{{ __('Total') }}</th><th class="text-end">%</th><th>{{ __('Grade') }}</th><th class="text-end">{{ __('GPA') }}</th><th>{{ __('Result') }}</th></tr></thead>
        <tbody>
          @foreach ($rows as $r)
            <tr>
              <td>{{ $r->merit_position ?? '—' }}</td>
              <td class="fw-semibold">{{ $r->student?->name ?? '—' }}</td>
              <td class="text-end">{{ $r->total_marks }} / {{ $r->total_possible }}</td>
              <td class="text-end">{{ $r->percentage }}%</td>
              <td>{{ $r->grade ?? '—' }}</td>
              <td class="text-end">{{ $r->gpa ?? '—' }}</td>
              <td>
                @if ($r->is_pass)<span class="badge text-bg-success">{{ __('Pass') }}</span>@else<span class="badge text-bg-danger">{{ __('Fail') }}</span>@endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div></div>
@endsection
