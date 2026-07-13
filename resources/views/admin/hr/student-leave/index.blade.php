@extends('layouts.admin')
@section('title', 'Student leave')
@section('content')
  @include('admin.partials.page-header', ['title' => 'Student leave requests', 'crumbs' => ['HR', 'Student leave']])

  <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-sm-4"><label class="form-label small text-muted mb-1">Status</label>
      <select name="status" class="form-select form-select-sm">
        @foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'] as $v => $l)
          <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
        @endforeach
      </select></div>
    <div class="col-sm-8"><button class="btn btn-sm btn-outline-primary">Filter</button>
      <a href="{{ route('admin.student-leave.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a></div>
  </div></form>

  @php $m = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary']; @endphp
  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Student</th><th>Type</th><th>From</th><th>To</th><th>Days</th><th>Status</th><th class="text-end" data-orderable="false">Actions</th></tr></thead>
      <tbody>
        @foreach ($requests as $r)
          <tr>
            <td class="fw-semibold">{{ $r->student?->name ?? '—' }} <span class="text-muted small">({{ $r->student?->student_id }})</span></td>
            <td>{{ $r->leaveType?->name ?? '—' }}</td>
            <td class="small">{{ optional($r->from_date)->format('d M Y') }}</td>
            <td class="small">{{ optional($r->to_date)->format('d M Y') }}</td>
            <td>{{ $r->working_days }}</td>
            <td><span class="badge text-bg-{{ $m[$r->status] ?? 'secondary' }}">{{ ucfirst($r->status) }}</span></td>
            <td class="text-end">
              @if ($r->status === 'pending')
                <form method="POST" action="{{ route('admin.student-leave.approve', $r->id) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Approve</button></form>
                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $r->id }}">Reject</button>
              @elseif ($r->status === 'approved')
                <form method="POST" action="{{ route('admin.student-leave.cancel', $r->id) }}" class="d-inline" onsubmit="return confirm('Cancel this approved leave?')">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning">Cancel</button></form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @foreach ($requests as $r)
    @if ($r->status === 'pending')
      <div class="modal fade" id="rejectModal{{ $r->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
        <form method="POST" action="{{ route('admin.student-leave.reject', $r->id) }}">
          @csrf @method('PATCH')
          <div class="modal-header"><h5 class="modal-title">Reject leave</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body"><label class="form-label">Reason</label><textarea name="reason" rows="2" class="form-control"></textarea></div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Reject</button></div>
        </form>
      </div></div></div>
    @endif
  @endforeach
@endsection
