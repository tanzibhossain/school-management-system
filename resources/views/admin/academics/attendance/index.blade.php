@extends('layouts.admin')
@section('title', __('Attendance'))
@section('content')
  @include('admin.partials.page-header', ['title' => 'Attendance register', 'crumbs' => ['Academics', 'Attendance']])

  <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-sm-4"><label class="form-label small text-muted mb-1">{{ __('Class') }}</label>
      <select name="class_id" id="fClass" class="form-select form-select-sm" required>
        <option value="">— select —</option>
        @foreach ($classes as $c)<option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->name }}</option>@endforeach
      </select></div>
    <div class="col-sm-3"><label class="form-label small text-muted mb-1">{{ __('Section') }}</label>
      <select name="section_id" id="fSection" class="form-select form-select-sm" data-sel="{{ $sectionId }}">
        <option value="">{{ __('All sections') }}</option>
      </select></div>
    <div class="col-sm-3"><label class="form-label small text-muted mb-1">{{ __('Date') }}</label>
      <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}"></div>
    <div class="col-sm-2"><button class="btn btn-sm btn-primary w-100">{{ __('Load') }}</button></div>
  </div></form>

  @if ($classId)
    @if ($roster->isEmpty())
      <div class="alert alert-warning">{{ __('No active students found for this class/section.') }}</div>
    @else
      <form method="POST" action="{{ route('admin.attendance.store') }}">
        @csrf
        <input type="hidden" name="class_id" value="{{ $classId }}">
        <input type="hidden" name="section_id" value="{{ $sectionId }}">
        <input type="hidden" name="date" value="{{ $date }}">
        <div class="card"><div class="card-body">
          <div class="d-flex justify-content-between mb-2">
            <div class="text-muted small">{{ $roster->count() }} students · {{ \Carbon\Carbon::parse($date)->format('D, d M Y') }}</div>
            <div class="btn-group btn-group-sm" role="group">
              <button type="button" class="btn btn-outline-success" onclick="setAll('present')">{{ __('All present') }}</button>
              <button type="button" class="btn btn-outline-danger" onclick="setAll('absent')">{{ __('All absent') }}</button>
            </div>
          </div>
          <table class="table table-hover align-middle">
            <thead><tr><th>#</th><th>{{ __('Student') }}</th><th style="width:52%">{{ __('Status') }}</th></tr></thead>
            <tbody>
              @foreach ($roster as $i => $r)
                <tr>
                  <td>{{ $i + 1 }}</td>
                  <td>{{ $r->name }} <span class="text-muted small">({{ $r->code }})</span></td>
                  <td>
                    <div class="btn-group btn-group-sm status-group" role="group">
                      @foreach (['present'=>'success','absent'=>'danger','late'=>'warning','half_day'=>'info','leave'=>'secondary'] as $st => $clr)
                        <input type="radio" class="btn-check" name="statuses[{{ $r->student_id }}]" id="s{{ $r->student_id }}{{ $st }}" value="{{ $st }}" @checked($r->status === $st)>
                        <label class="btn btn-outline-{{ $clr }}" for="s{{ $r->student_id }}{{ $st }}">{{ ucfirst(str_replace('_',' ',$st)) }}</label>
                      @endforeach
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
          <div class="text-end"><button class="btn btn-primary"><i class="bi bi-save"></i> {{ __('Save attendance') }}</button></div>
        </div></div>
      </form>
    @endif
  @else
    <div class="alert alert-info">{{ __('Select a class and date to load the register.') }}</div>
  @endif

  @push('scripts')
    <script>
      var SECTIONS = @json($sections);
      function fillSections() {
        var cls = document.getElementById('fClass'); var sec = document.getElementById('fSection');
        var cid = parseInt(cls.value, 10); var want = sec.getAttribute('data-sel');
        sec.innerHTML = '<option value="">All sections</option>';
        SECTIONS.filter(function (s) { return s.class_id === cid; }).forEach(function (s) {
          var o = document.createElement('option'); o.value = s.id; o.textContent = s.name;
          if (String(s.id) === String(want)) o.selected = true;
          sec.appendChild(o);
        });
      }
      function setAll(status) {
        document.querySelectorAll('.status-group input[value="' + status + '"]').forEach(function (r) { r.checked = true; });
      }
      document.getElementById('fClass').addEventListener('change', fillSections);
      fillSections();
    </script>
  @endpush
@endsection
