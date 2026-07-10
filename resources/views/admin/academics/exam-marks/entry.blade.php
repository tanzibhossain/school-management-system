@extends('layouts.admin')
@section('title', 'Mark entry')
@section('content')
  @php $subjectName = $division->examSubject?->subjectRelation?->subject?->name ?? 'Subject'; @endphp
  @include('admin.partials.page-header', [
    'title'  => $subjectName . ' — ' . $division->name,
    'crumbs' => ['Academics', 'Exams', $exam->title, 'Marks', $division->name],
  ])

  <div class="mb-3"><a href="{{ route('admin.exam-marks.index', $exam->id) }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Back to marks</a></div>

  @if ($roster->isEmpty())
    <div class="alert alert-warning">No active students found for this exam's class.</div>
  @else
    <form method="POST" action="{{ route('admin.exam-marks.entry.save', [$exam->id, $division->id]) }}">
      @csrf
      <div class="card"><div class="card-body">
        <div class="text-muted small mb-2">Max marks: <strong>{{ $division->max_marks }}</strong>. Tick “Absent” for students who did not sit; their mark is recorded as “Ab”.</div>
        <table class="table table-hover align-middle">
          <thead><tr><th>#</th><th>Student</th><th style="width:180px">Marks</th><th style="width:110px" class="text-center">Absent</th></tr></thead>
          <tbody>
            @foreach ($roster as $i => $r)
              <tr class="{{ $r->locked ? 'table-light' : '' }}">
                <td>{{ $i + 1 }}</td>
                <td>{{ $r->name }} <span class="text-muted small">({{ $r->code }})</span></td>
                <td>
                  <input type="number" step="0.01" min="0" max="{{ $division->max_marks }}" name="marks[{{ $r->student_id }}]"
                         class="form-control form-control-sm mark-input" value="{{ $r->is_absent ? '' : $r->obtained }}"
                         {{ $r->locked ? 'disabled' : '' }} {{ $r->is_absent ? 'disabled' : '' }} data-sid="{{ $r->student_id }}">
                </td>
                <td class="text-center">
                  <input type="checkbox" class="form-check-input absent-check" name="absent[{{ $r->student_id }}]" value="1"
                         @checked($r->is_absent) {{ $r->locked ? 'disabled' : '' }} data-sid="{{ $r->student_id }}">
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <div class="text-end"><button class="btn btn-primary"><i class="bi bi-save"></i> Save marks</button></div>
      </div></div>
    </form>
  @endif

  @push('scripts')
    <script>
      document.querySelectorAll('.absent-check').forEach(function (cb) {
        cb.addEventListener('change', function () {
          var input = document.querySelector('.mark-input[data-sid="' + cb.getAttribute('data-sid') + '"]');
          if (!input) return;
          input.disabled = cb.checked;
          if (cb.checked) input.value = '';
        });
      });
    </script>
  @endpush
@endsection
