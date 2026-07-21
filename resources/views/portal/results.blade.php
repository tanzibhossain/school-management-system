@extends('layouts.portal')
@section('title', __('Results'))
@section('heading', 'Results')
@section('content')

  <div class="card">
    <div class="card-header">{{ __('Published Exam Results') }}</div>
    <div class="card-body p-0">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr><th>{{ __('Exam') }}</th><th class="text-end">{{ __('Marks') }}</th><th class="text-end">%</th><th class="text-center">{{ __('Grade') }}</th><th class="text-center">{{ __('GPA') }}</th><th class="text-center">{{ __('Result') }}</th><th class="text-end">{{ __('Marksheet') }}</th></tr>
        </thead>
        <tbody>
          @forelse($results as $r)
            <tr>
              <td class="fw-medium">{{ $r->exam->title ?? 'Exam #' . $r->exam_id }}</td>
              <td class="text-end">{{ rtrim(rtrim(number_format($r->total_marks, 2), '0'), '.') }} / {{ rtrim(rtrim(number_format($r->total_possible, 2), '0'), '.') }}</td>
              <td class="text-end">{{ number_format($r->percentage, 2) }}%</td>
              <td class="text-center"><span class="badge text-bg-light">{{ $r->grade ?? '—' }}</span></td>
              <td class="text-center">{{ $r->gpa !== null ? number_format($r->gpa, 2) : '—' }}</td>
              <td class="text-center">
                @if($r->is_pass)<span class="badge text-bg-success">{{ __('Pass') }}</span>@else<span class="badge text-bg-danger">{{ __('Fail') }}</span>@endif
              </td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" target="_blank" href="{{ route('portal.results.marksheet', ['examId' => $r->exam_id, 'student' => $student->id]) }}"><i class="bi bi-download"></i> {{ __('PDF') }}</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted py-4">{{ __('No Results Published Yet.') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

@endsection
