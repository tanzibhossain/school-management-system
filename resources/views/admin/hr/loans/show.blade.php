@extends('layouts.admin')
@section('title', __('Loan'))
@section('content')
  @php $m = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary']; @endphp
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">{{ __('Home') }}</a></li><li class="breadcrumb-item">HR</li><li class="breadcrumb-item"><a href="{{ route('admin.staff-loans.index') }}" class="text-decoration-none">{{ __('Staff Loans') }}</a></li><li class="breadcrumb-item active">#{{ $loan->id }}</li></ol></nav>
      <h1 class="h4 mb-0">{{ $loan->staff?->name }} <span class="badge text-bg-{{ $m[$loan->status] ?? 'secondary' }} align-middle">{{ ucfirst($loan->status) }}</span></h1>
    </div>
    @if ($loan->status === 'pending')
      <div class="d-flex gap-2">
        <form method="POST" action="{{ route('admin.staff-loans.approve', $loan->id) }}" onsubmit="return confirm('Approve this loan? A repayment schedule will be generated.')">@csrf @method('PATCH')<button class="btn btn-success"><i class="bi bi-check2-all"></i> {{ __('Approve') }}</button></form>
        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">{{ __('Reject') }}</button>
        <form method="POST" action="{{ route('admin.staff-loans.cancel', $loan->id) }}" onsubmit="return confirm('Cancel this request?')">@csrf @method('PATCH')<button class="btn btn-outline-secondary">{{ __('Cancel') }}</button></form>
      </div>
    @endif
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card"><div class="card-header">{{ __('Details') }}</div><div class="card-body">
        <dl class="row mb-0">
          <dt class="col-6 text-muted">{{ __('Amount') }}</dt><dd class="col-6">{{ number_format((float) $loan->requested_amount, 2) }}</dd>
          <dt class="col-6 text-muted">{{ __('Installments') }}</dt><dd class="col-6">{{ $loan->installment_count }}</dd>
          <dt class="col-6 text-muted">{{ __('First Due') }}</dt><dd class="col-6">{{ optional($loan->start_date)->format('d M Y') }}</dd>
          <dt class="col-6 text-muted">{{ __('Reason') }}</dt><dd class="col-6">{{ $loan->reason }}</dd>
          @if ($loan->rejection_reason)<dt class="col-6 text-muted">{{ __('Rejection') }}</dt><dd class="col-6 text-danger">{{ $loan->rejection_reason }}</dd>@endif
        </dl>
      </div></div>
    </div>
    <div class="col-lg-7">
      <div class="card"><div class="card-header">{{ __('Repayment Schedule') }}</div><div class="card-body">
        @if ($loan->schedules->isEmpty())
          <p class="text-muted mb-0">{{ __('No Schedule Yet — Generated On Approval.') }}</p>
        @else
          <table class="table align-middle mb-0">
            <thead><tr><th>#</th><th>{{ __('Due Date') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Paid') }}</th></tr></thead>
            <tbody>
              @foreach ($loan->schedules as $s)
                <tr>
                  <td>{{ $s->installment_number }}</td>
                  <td class="small">{{ optional($s->due_date)->format('d M Y') }}</td>
                  <td class="text-end">{{ number_format((float) $s->amount, 2) }}</td>
                  <td>@if ($s->is_paid)<span class="badge text-bg-success">{{ __('Paid') }}</span>@else<span class="badge text-bg-light border text-muted">{{ __('Due') }}</span>@endif</td>
                </tr>
              @endforeach
            </tbody>
            <tfoot><tr class="table-light"><th colspan="2" class="text-end">{{ __('Total') }}</th><th class="text-end">{{ number_format((float) $loan->schedules->sum('amount'), 2) }}</th><th></th></tr></tfoot>
          </table>
        @endif
      </div></div>
    </div>
  </div>

  @if ($loan->status === 'pending')
    <div class="modal fade" id="rejectModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.staff-loans.reject', $loan->id) }}">
        @csrf @method('PATCH')
        <div class="modal-header"><h5 class="modal-title">{{ __('Reject Loan') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><label class="form-label">{{ __('Reason') }}</label><textarea name="reason" rows="2" class="form-control"></textarea></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-danger">{{ __('Reject') }}</button></div>
      </form>
    </div></div></div>
  @endif
@endsection
