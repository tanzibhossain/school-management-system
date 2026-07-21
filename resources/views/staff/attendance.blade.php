@extends('layouts.staff')
@section('title', __('Attendance'))
@section('heading', 'Attendance')
@section('content')

  @if($sections->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
      <i class="bi bi-calendar-check fs-3 d-block mb-2 opacity-50"></i>
      You are not assigned as a class teacher for any section, so there is no register to take.
    </div></div>
  @else
    {{-- Section + date picker --}}
    <form method="GET" class="card mb-3"><div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label small">{{ __('Section') }}</label>
          <select name="section_id" class="form-select" onchange="this.form.submit()">
            @foreach($sections as $s)
              <option value="{{ $s->id }}" @selected($section && $s->id === $section->id)>
                {{ $s->schoolClass->name ?? '' }} · Section {{ $s->name }}@if($s->shift) ({{ $s->shift->name }})@endif
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small">{{ __('Date') }}</label>
          <input type="date" name="date" class="form-control" value="{{ $date }}" max="{{ now()->format('Y-m-d') }}" onchange="this.form.submit()">
        </div>
        <div class="col-md-3 text-md-end">
          <span class="text-muted small">{{ $roster->count() }} students</span>
        </div>
      </div>
    </div></form>

    {{-- Register --}}
    @if($section)
      <form method="POST" action="{{ route('staff.attendance.store') }}">
        @csrf
        <input type="hidden" name="section_id" value="{{ $section->id }}">
        <input type="hidden" name="date" value="{{ $date }}">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>Register — {{ \Illuminate\Support\Carbon::parse($date)->format('D, j M Y') }}</span>
            <div class="btn-group btn-group-sm">
              <button type="button" class="btn btn-outline-secondary" onclick="setAll('present')">{{ __('All present') }}</button>
              <button type="button" class="btn btn-outline-secondary" onclick="setAll('absent')">{{ __('All absent') }}</button>
            </div>
          </div>
          <div class="card-body p-0">
            @if($roster->isEmpty())
              <div class="text-center text-muted py-4">{{ __('No active students in this section.') }}</div>
            @else
              <table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>{{ __('Student') }}</th><th style="width:340px">{{ __('Status') }}</th></tr></thead>
                <tbody>
                  @foreach($roster as $r)
                    <tr>
                      <td><div class="fw-medium">{{ $r->name }}</div><small class="text-muted">{{ $r->code }}</small></td>
                      <td>
                        <div class="btn-group btn-group-sm w-100" role="group">
                          @foreach(['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'half_day' => 'Half', 'leave' => 'Leave'] as $val => $lbl)
                            <input type="radio" class="btn-check" name="statuses[{{ $r->student_id }}]" id="st{{ $r->student_id }}{{ $val }}" value="{{ $val }}" @checked($r->status === $val)>
                            <label class="btn btn-outline-secondary" for="st{{ $r->student_id }}{{ $val }}">{{ $lbl }}</label>
                          @endforeach
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            @endif
          </div>
          @unless($roster->isEmpty())
            <div class="card-footer text-end"><button class="btn btn-primary"><i class="bi bi-save me-1"></i> {{ __('Save attendance') }}</button></div>
          @endunless
        </div>
      </form>
    @endif
  @endif

@push('scripts')
<script>
  function setAll(status) {
    document.querySelectorAll('input.btn-check[value="' + status + '"]').forEach(function (el) { el.checked = true; });
  }
</script>
@endpush
@endsection
