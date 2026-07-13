@extends('layouts.admin')
@section('title', 'Staff loans')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Staff loans',
    'crumbs' => ['HR', 'Staff loans'],
    'action' => ['label' => 'New loan', 'modal' => 'createModal'],
  ])

  <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-sm-4"><label class="form-label small text-muted mb-1">Status</label>
      <select name="status" class="form-select form-select-sm">
        @foreach (['' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'] as $v => $l)
          <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
        @endforeach
      </select></div>
    <div class="col-sm-8"><button class="btn btn-sm btn-outline-primary">Filter</button>
      <a href="{{ route('admin.staff-loans.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a></div>
  </div></form>

  @php $m = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary']; @endphp
  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Staff</th><th class="text-end">Amount</th><th>Installments</th><th>Start</th><th>Status</th><th class="text-end" data-orderable="false">Actions</th></tr></thead>
      <tbody>
        @foreach ($loans as $l)
          <tr>
            <td class="fw-semibold">{{ $l->staff?->name ?? '—' }} <span class="text-muted small">({{ $l->staff?->employee_id }})</span></td>
            <td class="text-end">{{ number_format((float) $l->requested_amount, 2) }}</td>
            <td>{{ $l->installment_count }}</td>
            <td class="small">{{ optional($l->start_date)->format('d M Y') }}</td>
            <td><span class="badge text-bg-{{ $m[$l->status] ?? 'secondary' }}">{{ ucfirst($l->status) }}</span></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.staff-loans.show', $l->id) }}">Open</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.staff-loans.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">New loan request</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Staff <span class="text-danger">*</span></label>
          <select name="staff_id" class="form-select js-select" required>
            <option value="">— select —</option>
            @foreach ($staff as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ $s->employee_id }})</option>@endforeach
          </select></div>
        <div class="col-md-6"><label class="form-label">Amount <span class="text-danger">*</span></label>
          <input type="number" step="0.01" min="1" name="requested_amount" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Installments <span class="text-danger">*</span></label>
          <input type="number" min="1" max="120" name="installment_count" class="form-control" value="12" required></div>
        <div class="col-md-6"><label class="form-label">First due date <span class="text-danger">*</span></label>
          <input type="date" name="start_date" class="form-control" required></div>
        <div class="col-12"><label class="form-label">Reason <span class="text-danger">*</span></label>
          <input name="reason" class="form-control" required></div>
        <div class="col-12"><div class="alert alert-info py-2 mb-0 small">Interest-free. The repayment schedule is generated on approval and repaid via payroll.</div></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create</button></div>
    </form>
  </div></div></div>
@endsection
