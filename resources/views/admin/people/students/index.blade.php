@extends('layouts.admin')
@section('title', 'Students')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Students',
    'crumbs' => ['People', 'Students'],
    'action' => ['label' => 'Enrol student', 'url' => route('admin.students.create')],
  ])

  <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-sm-4"><label class="form-label small text-muted mb-1">Class</label>
      <select name="class_id" class="form-select form-select-sm">
        <option value="">All classes</option>
        @foreach ($classes as $c)
          <option value="{{ $c->id }}" @selected(($filters['class_id'] ?? null) == $c->id)>{{ $c->name }}</option>
        @endforeach
      </select></div>
    <div class="col-sm-4"><label class="form-label small text-muted mb-1">Status</label>
      <select name="status" class="form-select form-select-sm">
        @foreach (['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $v => $l)
          <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
        @endforeach
      </select></div>
    <div class="col-sm-4"><button class="btn btn-sm btn-outline-primary">Filter</button>
      <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a></div>
  </div></form>

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Student ID</th><th>Name</th><th>Class</th><th>Section</th><th>Guardian</th><th>Status</th><th class="text-end" data-orderable="false">Actions</th></tr></thead>
      <tbody>
        @foreach ($students as $st)
          <tr>
            <td><code>{{ $st->student_id }}</code></td>
            <td class="fw-semibold">{{ $st->name }}</td>
            <td>{{ $st->currentAcademic?->schoolClass?->name ?? '—' }}</td>
            <td>{{ $st->currentAcademic?->section?->name ?? '—' }}</td>
            <td>{{ $st->primaryGuardian?->name ?? '—' }}</td>
            <td>
              <span class="badge {{ $st->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }}">{{ ucfirst($st->status) }}</span>
            </td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.students.show', $st->id) }}">View</a>
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $st->id }}">Edit</button>
              @if ($st->status === 'active')
                <form method="POST" action="{{ route('admin.students.deactivate', $st->id) }}" class="d-inline" onsubmit="return confirm('Deactivate {{ $st->name }}?')">
                  @csrf @method('PATCH')
                  <button class="btn btn-sm btn-outline-danger">Deactivate</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @php
    $genderOptions = function ($selected = null) {
      $out = '<option value="">—</option>';
      foreach (['male'=>'Male','female'=>'Female','other'=>'Other'] as $v=>$l) { $out .= '<option value="'.$v.'"'.($selected===$v?' selected':'').'>'.$l.'</option>'; }
      return $out;
    };
  @endphp
  @foreach ($students as $st)
    <div class="modal fade" id="editModal{{ $st->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.students.update', $st->id) }}">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Edit student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
          <div class="col-md-8"><label class="form-label">Name <span class="text-danger">*</span></label>
            <input name="name" class="form-control" value="{{ $st->name }}" required></div>
          <div class="col-md-4"><label class="form-label">Gender <span class="text-danger">*</span></label>
            <select name="gender" class="form-select" required>{!! $genderOptions($st->gender) !!}</select></div>
          <div class="col-md-4"><label class="form-label">Date of birth</label>
            <input type="date" name="dob" class="form-control" value="{{ optional($st->dob)->format('Y-m-d') }}"></div>
          <div class="col-md-4"><label class="form-label">Blood group</label>
            <select name="blood_group" class="form-select">
              <option value="">—</option>
              @foreach (['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $bg)
                <option value="{{ $bg }}" @selected($st->blood_group===$bg)>{{ $bg }}</option>
              @endforeach
            </select></div>
          <div class="col-md-4"><label class="form-label">Religion</label>
            <input name="religion" class="form-control" value="{{ $st->religion }}"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
      </form>
    </div></div></div>
  @endforeach
@endsection
