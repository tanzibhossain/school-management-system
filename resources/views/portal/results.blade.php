@extends('layouts.portal')
@section('title', 'Results')
@section('heading', 'Results')
@section('content')

  <div class="card">
    <div class="card-header">Published exam results</div>
    <div class="card-body p-0">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr><th>Exam</th><th class="text-end">Marks</th><th class="text-end">%</th><th class="text-center">Grade</th><th class="text-center">GPA</th><th class="text-center">Result</th></tr>
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
                @if($r->is_pass)<span class="badge text-bg-success">Pass</span>@else<span class="badge text-bg-danger">Fail</span>@endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No results published yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

@endsection
