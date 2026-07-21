@extends('layouts.staff')
@section('title', __('Enter Marks'))
@section('heading', 'Enter Marks')
@section('content')

  <div class="mb-3">
    <a href="{{ route('staff.marks') }}" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>{{ __('Back To Marks') }}</a>
    <h1 class="h5 mt-2 mb-1">{{ $exam->title }} — {{ $division->examSubject->subjectRelation->subject->name ?? '' }} · {{ $division->name }}</h1>
    <p class="text-muted small mb-0">Max marks {{ rtrim(rtrim(number_format($division->max_marks, 2), '0'), '.') }}. Tick <strong>Ab</strong> {{ __('For Absent Students.') }}</p>
  </div>

  <form method="POST" action="{{ route('staff.marks.save', [$exam->id, $division->id]) }}">
    @csrf
    <div class="card">
      <div class="card-body p-0">
        @if($roster->isEmpty())
          <div class="text-center text-muted py-4">{{ __('No Active Students In This Class.') }}</div>
        @else
          <table class="table align-middle mb-0">
            <thead class="table-light"><tr><th>{{ __('Student') }}</th><th style="width:160px">{{ __('Marks') }}</th><th style="width:80px" class="text-center">{{ __('Absent') }}</th></tr></thead>
            <tbody>
              @foreach($roster as $r)
                <tr @if($r->locked) class="table-light" @endif>
                  <td><div class="fw-medium">{{ $r->name }}</div><small class="text-muted">{{ $r->code }}</small></td>
                  <td>
                    <input type="number" step="0.01" min="0" max="{{ $division->max_marks }}"
                      name="marks[{{ $r->student_id }}]" value="{{ $r->obtained }}"
                      class="form-control form-control-sm mark-input" data-sid="{{ $r->student_id }}"
                      @disabled($r->is_absent || $r->locked)>
                  </td>
                  <td class="text-center">
                    <input type="checkbox" class="form-check-input absent-check" name="absent[{{ $r->student_id }}]" value="1"
                      data-sid="{{ $r->student_id }}" @checked($r->is_absent) @disabled($r->locked)>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>
      @unless($roster->isEmpty())
        <div class="card-footer text-end"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> {{ __('Save Marks') }}</button></div>
      @endunless
    </div>
  </form>

@push('scripts')
<script>
  // Disable the marks input when a student is marked absent.
  document.querySelectorAll('.absent-check').forEach(function (chk) {
    chk.addEventListener('change', function () {
      var input = document.querySelector('.mark-input[data-sid="' + this.dataset.sid + '"]');
      if (!input) return;
      input.disabled = this.checked;
      if (this.checked) input.value = '';
    });
  });
</script>
@endpush
@endsection
