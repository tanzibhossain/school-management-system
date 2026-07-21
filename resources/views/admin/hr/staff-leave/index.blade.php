@extends('layouts.admin')
@section('title', __('Staff Leave'))
@section('content')
  @include('admin.partials.page-header', ['title' => 'Staff leave requests', 'crumbs' => ['HR', 'Staff leave']])

  <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-sm-4"><label class="form-label small text-muted mb-1">{{ __('Status') }}</label>
      <select name="status" class="form-select form-select-sm">
        @foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'] as $v => $l)
          <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
        @endforeach
      </select></div>
    <div class="col-sm-8"><button class="btn btn-sm btn-outline-primary">{{ __('Filter') }}</button>
      <a href="{{ route('admin.staff-leave.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Reset') }}</a></div>
  </div></form>

  @php $m = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary']; @endphp
  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Staff') }}</th><th>{{ __('Type') }}</th><th>{{ __('From') }}</th><th>To</th><th>{{ __('Days') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($requests as $r)
          <tr>
            <td class="fw-semibold">{{ $r->staff?->name ?? '—' }} <span class="text-muted small">({{ $r->staff?->employee_id }})</span></td>
            <td>{{ $r->leaveType?->name ?? '—' }}</td>
            <td class="small">{{ optional($r->from_date)->format('d M Y') }}</td>
            <td class="small">{{ optional($r->to_date)->format('d M Y') }}</td>
            <td>{{ $r->working_days }}</td>
            <td><span class="badge text-bg-{{ $m[$r->status] ?? 'secondary' }}">{{ ucfirst($r->status) }}</span></td>
            <td class="text-end">
              @if ($r->status === 'pending')
                <form method="POST" action="{{ route('admin.staff-leave.approve', $r->id) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">{{ __('Approve') }}</button></form>
                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $r->id }}">{{ __('Reject') }}</button>
              @elseif ($r->status === 'approved')
                <form method="POST" action="{{ route('admin.staff-leave.cancel', $r->id) }}" class="d-inline" onsubmit="return confirm('Cancel this approved leave?')">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning">{{ __('Cancel') }}</button></form>
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
        <form method="POST" action="{{ route('admin.staff-leave.reject', $r->id) }}">
          @csrf @method('PATCH')
          <div class="modal-header"><h5 class="modal-title">{{ __('Reject Leave') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
          <div class="modal-body"><label class="form-label">{{ __('Reason') }}</label><textarea name="reason" rows="2" class="form-control"></textarea></div>
          <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-danger">{{ __('Reject') }}</button></div>
        </form>
      </div></div></div>
    @endif
  @endforeach
@endsection
