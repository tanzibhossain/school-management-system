@extends('layouts.admin')
@section('title', __('Class Routine'))
@section('content')
  @include('admin.partials.page-header', ['title' => 'Class routine', 'crumbs' => ['Setup', 'Class routine']])

  <ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.routine.index') }}">{{ __('Class Routine') }}</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.routine-setup.index') }}">Periods &amp; rooms</a></li>
  </ul>

  <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-sm-4"><label class="form-label small text-muted mb-1">{{ __('Class') }}</label>
      <select name="class_id" id="rClass" class="form-select form-select-sm" required>
        <option value="">— select —</option>
        @foreach ($classes as $c)<option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->name }}</option>@endforeach
      </select></div>
    <div class="col-sm-4"><label class="form-label small text-muted mb-1">{{ __('Section') }}</label>
      <select name="section_id" id="rSection" class="form-select form-select-sm" data-sel="{{ $sectionId }}" required><option value="">— select —</option></select></div>
    <div class="col-sm-4"><button class="btn btn-sm btn-primary">{{ __('Load') }}</button>
      @if ($classId && $sectionId && $periods->isNotEmpty())<button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addModal">{{ __('Add Class') }}</button>@endif
    </div>
  </div></form>

  @if ($classId && $sectionId)
    @if ($periods->isEmpty())
      <div class="alert alert-warning">{{ __('No Periods Defined — Add Them Under') }} <a href="{{ route('admin.routine-setup.index') }}">Periods &amp; rooms</a> {{ __('First.') }}</div>
    @else
      <div class="card"><div class="card-body table-responsive">
        <table class="table table-bordered align-middle text-center mb-0">
          <thead><tr><th style="width:14%">{{ __('Period') }}</th>@foreach ($days as $d)<th class="text-capitalize">{{ $d }}</th>@endforeach</tr></thead>
          <tbody>
            @foreach ($periods as $p)
              <tr>
                <td class="text-start"><span class="fw-semibold">{{ $p->name }}</span><br><span class="small text-muted">{{ \Illuminate\Support\Str::of($p->start_time)->substr(0,5) }}–{{ \Illuminate\Support\Str::of($p->end_time)->substr(0,5) }}</span></td>
                @foreach ($days as $d)
                  @php $cell = $cells[$p->id . ':' . $d] ?? null; @endphp
                  <td>
                    @if ($cell)
                      <div class="small">
                        <span class="fw-semibold">{{ $cell->subject?->name ?? '—' }}</span><br>
                        <span class="text-muted">{{ $cell->teacher?->name ?? 'No teacher' }}</span><br>
                        <span class="badge text-bg-light border text-muted">{{ $cell->room?->name }}</span>
                        <form method="POST" action="{{ route('admin.routine.destroy', $cell->id) }}" class="d-inline" onsubmit="return confirm('Remove?')">@csrf @method('DELETE')<button class="btn btn-sm btn-link text-danger p-0 ms-1">✕</button></form>
                      </div>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                @endforeach
              </tr>
            @endforeach
          </tbody>
        </table>
      </div></div>

      <div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form method="POST" action="{{ route('admin.routine.store') }}">
          @csrf
          <input type="hidden" name="class_id" value="{{ $classId }}">
          <input type="hidden" name="section_id" value="{{ $sectionId }}">
          <div class="modal-header"><h5 class="modal-title">{{ __('Add To Routine') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body row g-3">
            <div class="col-md-6"><label class="form-label">{{ __('Day') }} <span class="text-danger">*</span></label>
              <select name="day_of_week" class="form-select" required>@foreach ($days as $d)<option value="{{ $d }}" class="text-capitalize">{{ ucfirst($d) }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">{{ __('Period') }} <span class="text-danger">*</span></label>
              <select name="period_id" class="form-select" required>@foreach ($periods as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label">{{ __('Subject') }} <span class="text-danger">*</span></label>
              <select name="subject_id" class="form-select js-select" required>
                <option value="">— select —</option>
                @foreach ($subjects as $sr)<option value="{{ $sr->subject_id }}">{{ $sr->subject?->name ?? ('Subject #' . $sr->subject_id) }}</option>@endforeach
              </select>
              @if ($subjects->isEmpty())<div class="form-text text-danger">{{ __('No Subjects Mapped To This Class.') }}</div>@endif
            </div>
            <div class="col-md-6"><label class="form-label">{{ __('Teacher') }}</label>
              <select name="teacher_id" class="form-select js-select"><option value="">— none —</option>@foreach ($teachers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></div>
            <div class="col-md-6"><label class="form-label">{{ __('Room') }} <span class="text-danger">*</span></label>
              <select name="room_id" class="form-select" required><option value="">— select —</option>@foreach ($rooms as $r)<option value="{{ $r->id }}">{{ $r->name }}</option>@endforeach</select></div>
          </div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Add') }}</button></div>
        </form>
      </div></div></div>
    @endif
  @else
    <div class="alert alert-info">{{ __('Select A Class And Section To View/edit Its Routine.') }}</div>
  @endif

  @push('scripts')
    <script>
      var SECTIONS = @json($sections);
      var cls = document.getElementById('rClass'); var sec = document.getElementById('rSection');
      function fill() {
        var cid = parseInt(cls.value, 10); var want = sec.getAttribute('data-sel');
        sec.innerHTML = '<option value="">— select —</option>';
        SECTIONS.filter(function (s) { return s.class_id === cid; }).forEach(function (s) {
          var o = document.createElement('option'); o.value = s.id; o.textContent = s.name;
          if (String(s.id) === String(want)) o.selected = true;
          sec.appendChild(o);
        });
      }
      cls.addEventListener('change', fill); fill();
    </script>
  @endpush
@endsection
