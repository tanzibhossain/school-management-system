@extends('layouts.admin')
@section('title', $student->name)
@section('content')
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Home</a></li><li class="breadcrumb-item">People</li><li class="breadcrumb-item"><a href="{{ route('admin.students.index') }}" class="text-decoration-none">Students</a></li><li class="breadcrumb-item active">{{ $student->name }}</li></ol></nav>
      <h1 class="h4 mb-0">{{ $student->name }} <span class="text-muted">({{ $student->student_id }})</span>
        <span class="badge {{ $student->status === 'active' ? 'text-bg-success' : 'text-bg-secondary' }} align-middle text-capitalize">{{ $student->status }}</span>
      </h1>
    </div>
    @if ($student->status === 'active')
      <div class="d-flex gap-2">
        <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#transferModal">Transfer</button>
        <form method="POST" action="{{ route('admin.students.deactivate', $student->id) }}" onsubmit="return confirm('Deactivate {{ $student->name }}?')">@csrf @method('PATCH')<button class="btn btn-outline-danger">Deactivate</button></form>
      </div>
    @endif
  </div>

  <ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-academics">Academics</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-guardians">Guardians ({{ $student->guardians->count() }})</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-subjects">Subjects ({{ $subjects->count() }})</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-invoices">Invoices ({{ $invoices->count() }})</button></li>
  </ul>

  <div class="tab-content">
    <div class="tab-pane fade show active" id="tab-academics">
      <div class="card"><div class="card-body">
        <div class="row g-3 mb-3">
          <div class="col-md-3"><span class="text-muted small">Gender</span><br><span class="text-capitalize">{{ $student->gender }}</span></div>
          <div class="col-md-3"><span class="text-muted small">Date of birth</span><br>{{ optional($student->dob)->format('d M Y') ?? '—' }}</div>
          <div class="col-md-3"><span class="text-muted small">Blood group</span><br>{{ $student->blood_group ?? '—' }}</div>
          <div class="col-md-3"><span class="text-muted small">Religion</span><br>{{ $student->religion ?? '—' }}</div>
        </div>
        <h2 class="h6">Enrolment history</h2>
        <table class="table align-middle mb-0">
          <thead><tr><th>Year</th><th>Class</th><th>Section</th><th>Roll</th><th>Current</th></tr></thead>
          <tbody>
            @forelse ($student->academics->sortByDesc('is_current') as $a)
              <tr>
                <td>{{ $years[$a->academic_year_id] ?? '—' }}</td>
                <td>{{ $a->schoolClass?->name ?? '—' }}</td>
                <td>{{ $a->section?->name ?? '—' }}</td>
                <td>{{ $a->roll_number ?? '—' }}</td>
                <td>@if ($a->is_current)<span class="badge text-bg-success">Current</span>@endif</td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-muted">No enrolment records.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div></div>
    </div>

    <div class="tab-pane fade" id="tab-guardians">
      <div class="card"><div class="card-body">
        @if ($student->guardians->isEmpty())
          <p class="text-muted mb-0">No guardians recorded.</p>
        @else
          <table class="table align-middle mb-0">
            <thead><tr><th>Name</th><th>Relation</th><th>Phone</th><th>Email</th><th>Primary</th></tr></thead>
            <tbody>
              @foreach ($student->guardians as $g)
                <tr>
                  <td class="fw-semibold">{{ $g->name }}</td>
                  <td class="text-capitalize">{{ str_replace('_', ' ', $g->relation) }}</td>
                  <td>{{ $g->phone ?? '—' }}</td>
                  <td>{{ $g->email ?? '—' }}</td>
                  <td>{!! $g->is_primary ? '<i class="bi bi-check-lg text-success"></i>' : '—' !!}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div></div>
    </div>

    <div class="tab-pane fade" id="tab-subjects">
      <div class="card"><div class="card-body">
        @if ($subjects->isEmpty())
          <p class="text-muted mb-0">No optional/4th-subject enrolments.</p>
        @else
          <table class="table align-middle mb-0">
            <thead><tr><th>Subject</th><th>Optional</th></tr></thead>
            <tbody>
              @foreach ($subjects as $s)
                <tr>
                  <td class="fw-semibold">{{ $s->subjectRelation?->subject?->name ?? '—' }}</td>
                  <td>{!! $s->is_optional ? '<span class="badge text-bg-info">Optional</span>' : '<span class="badge text-bg-light border text-muted">Compulsory</span>' !!}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div></div>
    </div>

    <div class="tab-pane fade" id="tab-invoices">
      <div class="card"><div class="card-body">
        @if ($invoices->isEmpty())
          <p class="text-muted mb-0">No invoices.</p>
        @else
          @php $m = ['paid'=>'success','partial'=>'warning','unpaid'=>'secondary','cancelled'=>'dark','waived'=>'info']; @endphp
          <table class="table table-hover align-middle mb-0">
            <thead><tr><th>Invoice #</th><th class="text-end">Due</th><th class="text-end">Paid</th><th>Status</th><th class="text-end"></th></tr></thead>
            <tbody>
              @foreach ($invoices as $inv)
                <tr>
                  <td><code>{{ $inv->invoice_number }}</code></td>
                  <td class="text-end">{{ number_format((float) $inv->amount_due, 2) }}</td>
                  <td class="text-end">{{ number_format((float) $inv->amount_paid, 2) }}</td>
                  <td><span class="badge text-bg-{{ $m[$inv->status] ?? 'secondary' }}">{{ ucfirst($inv->status) }}</span></td>
                  <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.invoices.show', $inv->id) }}">Open</a></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div></div>
    </div>
  </div>

  @if ($student->status === 'active')
    <div class="modal fade" id="transferModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.students.transfer', $student->id) }}">
        @csrf @method('PATCH')
        <div class="modal-header"><h5 class="modal-title">Transfer student</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><label class="form-label">Reason</label><input name="reason" class="form-control" placeholder="e.g. Moved city"><div class="form-text">Marks the student as transferred and revokes portal access.</div></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-warning">Transfer</button></div>
      </form>
    </div></div></div>
  @endif
@endsection
