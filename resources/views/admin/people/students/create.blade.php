@extends('layouts.admin')
@section('title', 'Enrol student')
@section('content')
  @include('admin.partials.page-header', ['title' => 'Enrol student', 'crumbs' => ['People', 'Students', 'Enrol']])

  <div class="mb-3"><a href="{{ route('admin.students.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Back to students</a></div>

  <form method="POST" action="{{ route('admin.students.store') }}">
    @csrf
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="card"><div class="card-header">Student</div><div class="card-body row g-3">
          <div class="col-md-8"><label class="form-label">Name <span class="text-danger">*</span></label>
            <input name="name" class="form-control" value="{{ old('name') }}" required></div>
          <div class="col-md-4"><label class="form-label">Gender <span class="text-danger">*</span></label>
            <select name="gender" class="form-select" required>
              <option value="">—</option>
              @foreach (['male'=>'Male','female'=>'Female','other'=>'Other'] as $v=>$l)
                <option value="{{ $v }}" @selected(old('gender')===$v)>{{ $l }}</option>
              @endforeach
            </select></div>
          <div class="col-md-4"><label class="form-label">Date of birth</label>
            <input type="date" name="dob" class="form-control" value="{{ old('dob') }}"></div>
          <div class="col-md-4"><label class="form-label">Blood group</label>
            <select name="blood_group" class="form-select">
              <option value="">—</option>
              @foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                <option value="{{ $bg }}" @selected(old('blood_group')===$bg)>{{ $bg }}</option>
              @endforeach
            </select></div>
          <div class="col-md-4"><label class="form-label">Religion</label>
            <input name="religion" class="form-control" value="{{ old('religion') }}"></div>
          <div class="col-md-6"><label class="form-label">Admission number <span class="text-danger">*</span></label>
            <input name="admission_number" class="form-control" value="{{ old('admission_number') }}" required></div>
        </div></div>

        <div class="card mt-4"><div class="card-header">Primary guardian <span class="text-muted small">(optional)</span></div><div class="card-body row g-3">
          <div class="col-md-7"><label class="form-label">Name</label>
            <input name="guardian_name" class="form-control" value="{{ old('guardian_name') }}"></div>
          <div class="col-md-5"><label class="form-label">Relation</label>
            <select name="guardian_relation" class="form-select">
              @foreach (['father'=>'Father','mother'=>'Mother','local_guardian'=>'Local guardian','other'=>'Other'] as $v=>$l)
                <option value="{{ $v }}" @selected(old('guardian_relation')===$v)>{{ $l }}</option>
              @endforeach
            </select></div>
          <div class="col-md-6"><label class="form-label">Phone</label>
            <input name="guardian_phone" class="form-control" value="{{ old('guardian_phone') }}"></div>
          <div class="col-md-6"><label class="form-label">Email</label>
            <input type="email" name="guardian_email" class="form-control" value="{{ old('guardian_email') }}"></div>
        </div></div>
      </div>

      <div class="col-lg-6">
        <div class="card"><div class="card-header">Academic placement</div><div class="card-body row g-3">
          <div class="col-md-6"><label class="form-label">Academic year <span class="text-danger">*</span></label>
            <select name="academic_year_id" class="form-select" required>
              <option value="">— select —</option>
              @foreach ($years as $y)
                <option value="{{ $y->id }}" @selected(old('academic_year_id', optional($years->firstWhere('is_current', true))->id)==$y->id)>{{ $y->year }}{{ $y->is_current ? ' (current)' : '' }}</option>
              @endforeach
            </select></div>
          <div class="col-md-6"><label class="form-label">Class <span class="text-danger">*</span></label>
            <select name="class_id" id="class_id" class="form-select" required>
              <option value="">— select —</option>
              @foreach ($classes as $c)
                <option value="{{ $c->id }}" @selected(old('class_id')==$c->id)>{{ $c->name }}</option>
              @endforeach
            </select></div>
          <div class="col-md-6"><label class="form-label">Section <span class="text-danger">*</span></label>
            <select name="section_id" id="section_id" class="form-select" required data-old="{{ old('section_id') }}">
              <option value="">— select class first —</option>
            </select></div>
          <div class="col-md-6"><label class="form-label">Roll number</label>
            <input name="roll_number" class="form-control" value="{{ old('roll_number') }}"></div>
          <div class="col-md-4"><label class="form-label">Version</label>
            <select name="version_id" class="form-select"><option value="">—</option>
              @foreach ($versions as $v)<option value="{{ $v->id }}" @selected(old('version_id')==$v->id)>{{ $v->name }}</option>@endforeach
            </select></div>
          <div class="col-md-4"><label class="form-label">Group</label>
            <select name="group_id" class="form-select"><option value="">—</option>
              @foreach ($groups as $g)<option value="{{ $g->id }}" @selected(old('group_id')==$g->id)>{{ $g->name }}</option>@endforeach
            </select></div>
          <div class="col-md-4"><label class="form-label">Shift</label>
            <select name="shift_id" class="form-select"><option value="">—</option>
              @foreach ($shifts as $s)<option value="{{ $s->id }}" @selected(old('shift_id')==$s->id)>{{ $s->name }}</option>@endforeach
            </select></div>
        </div></div>
      </div>
    </div>

    <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Enrol student</button></div>
  </form>

  @push('scripts')
    <script>
      (function () {
        var sections = @json($sections);
        var classSel = document.getElementById('class_id');
        var secSel = document.getElementById('section_id');
        function refill() {
          var cid = parseInt(classSel.value, 10);
          var want = secSel.getAttribute('data-old');
          secSel.innerHTML = '<option value="">— select —</option>';
          sections.filter(function (s) { return s.class_id === cid; }).forEach(function (s) {
            var o = document.createElement('option'); o.value = s.id; o.textContent = s.name;
            if (String(s.id) === String(want)) o.selected = true;
            secSel.appendChild(o);
          });
        }
        classSel.addEventListener('change', refill);
        if (classSel.value) refill();
      })();
    </script>
  @endpush
@endsection
