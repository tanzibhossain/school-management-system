@extends('layouts.admin')
@section('title', $exam->title)
@section('content')
  @php
    $m = ['draft'=>'secondary','published'=>'primary','completed'=>'success'];
    $editable = $exam->status !== 'completed';
  @endphp

  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Home</a></li><li class="breadcrumb-item">Academics</li><li class="breadcrumb-item"><a href="{{ route('admin.exams.index') }}" class="text-decoration-none">Exams</a></li><li class="breadcrumb-item active">{{ $exam->title }}</li></ol></nav>
      <h1 class="h4 mb-0">{{ $exam->title }} <span class="badge text-bg-{{ $m[$exam->status] ?? 'secondary' }} align-middle">{{ ucfirst($exam->status) }}</span></h1>
      <div class="text-muted small mt-1">{{ $exam->examType?->name }} · {{ $exam->schoolClass?->name }} · {{ optional($exam->start_date)->format('d M') }}–{{ optional($exam->end_date)->format('d M Y') }}</div>
    </div>
    <div class="d-flex gap-2">
      @if ($exam->status === 'draft')
        <form method="POST" action="{{ route('admin.exams.publish', $exam->id) }}" onsubmit="return confirm('Publish this exam?')">@csrf @method('PATCH')<button class="btn btn-primary"><i class="bi bi-send"></i> Publish</button></form>
      @elseif ($exam->status === 'published')
        <form method="POST" action="{{ route('admin.exams.complete', $exam->id) }}" onsubmit="return confirm('Mark this exam completed?')">@csrf @method('PATCH')<button class="btn btn-success"><i class="bi bi-check2-all"></i> Mark completed</button></form>
      @endif
    </div>
  </div>

  <div class="card"><div class="card-header d-flex justify-content-between align-items-center">
    <span>Subjects</span>
    @if ($editable)<button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addSubjectModal"><i class="bi bi-plus-lg"></i> Add subject</button>@endif
  </div><div class="card-body">
    @if ($exam->subjects->isEmpty())
      <p class="text-muted mb-0">No subjects scheduled yet.</p>
    @else
      <table class="table align-middle mb-0">
        <thead><tr><th>Subject</th><th>Date</th><th class="text-end">Full marks</th><th class="text-end">Pass marks</th>@if ($editable)<th></th>@endif</tr></thead>
        <tbody>
          @foreach ($exam->subjects as $s)
            <tr>
              <td class="fw-semibold">{{ $s->subjectRelation?->subject?->name ?? '—' }}</td>
              <td>{{ optional($s->exam_date)->format('d M Y') ?? '—' }}</td>
              <td class="text-end">{{ $s->full_marks }}</td>
              <td class="text-end">{{ $s->pass_marks }}</td>
              @if ($editable)
                <td class="text-end">
                  <form method="POST" action="{{ route('admin.exams.subjects.destroy', [$exam->id, $s->id]) }}" onsubmit="return confirm('Remove this subject?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger">Remove</button>
                  </form>
                </td>
              @endif
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div></div>

  @if ($editable)
    <div class="modal fade" id="addSubjectModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.exams.subjects.store', $exam->id) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">Add subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
          <div class="col-12"><label class="form-label">Subject <span class="text-danger">*</span></label>
            <select name="subject_relation_id" class="form-select js-select" required>
              <option value="">— select —</option>
              @foreach ($subjectRelations as $sr)<option value="{{ $sr->id }}">{{ $sr->subject?->name ?? ('Subject #' . $sr->subject_id) }}</option>@endforeach
            </select>
            @if ($subjectRelations->isEmpty())<div class="form-text text-danger">No subjects mapped to this class yet — add them under Setup → Subjects.</div>@endif
          </div>
          <div class="col-md-4"><label class="form-label">Exam date <span class="text-danger">*</span></label>
            <input type="date" name="exam_date" class="form-control" value="{{ optional($exam->start_date)->format('Y-m-d') }}" required></div>
          <div class="col-md-4"><label class="form-label">Start time <span class="text-danger">*</span></label>
            <input type="time" name="start_time" class="form-control" value="09:00" required></div>
          <div class="col-md-4"><label class="form-label">End time <span class="text-danger">*</span></label>
            <input type="time" name="end_time" class="form-control" value="11:00" required></div>
          <div class="col-md-6"><label class="form-label">Full marks <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="1" name="full_marks" class="form-control" value="100" required></div>
          <div class="col-md-6"><label class="form-label">Pass marks <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" name="pass_marks" class="form-control" value="33" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Add</button></div>
      </form>
    </div></div></div>
  @endif
@endsection
