@extends('layouts.admin')
@section('title', __('Invoices'))
@section('content')
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">{{ __('Home') }}</a></li><li class="breadcrumb-item">{{ __('Finance') }}</li><li class="breadcrumb-item active">{{ __('Invoices') }}</li></ol></nav>
      <h1 class="h4 mb-0">{{ __('Invoices') }}</h1>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#bulkModal"><i class="bi bi-collection"></i> {{ __('Bulk Generate') }}</button>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#singleModal"><i class="bi bi-plus-lg"></i> {{ __('Generate Invoice') }}</button>
    </div>
  </div>

  <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-sm-4"><label class="form-label small text-muted mb-1">{{ __('Status') }}</label>
      <select name="status" class="form-select form-select-sm">
        @foreach (['' => 'All', 'unpaid' => 'Unpaid', 'partial' => 'Partial', 'paid' => 'Paid', 'cancelled' => 'Cancelled', 'waived' => 'Waived'] as $v => $l)
          <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
        @endforeach
      </select></div>
    <div class="col-sm-4"><button class="btn btn-sm btn-outline-primary">{{ __('Filter') }}</button>
      <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Reset') }}</a></div>
  </div></form>

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Invoice #</th><th>{{ __('Student') }}</th><th>{{ __('Month') }}</th><th>{{ __('Due') }}</th><th>{{ __('Paid') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($invoices as $inv)
          <tr>
            <td><code>{{ $inv->invoice_number }}</code></td>
            <td>{{ $inv->student?->name ?? '—' }}</td>
            <td>{{ $inv->month ? \Carbon\Carbon::create()->month($inv->month)->format('M') : '—' }}</td>
            <td>{{ number_format((float) $inv->amount_due, 2) }}</td>
            <td>{{ number_format((float) $inv->amount_paid, 2) }}</td>
            <td>
              @php $map = ['paid'=>'success','partial'=>'warning','unpaid'=>'secondary','cancelled'=>'dark','waived'=>'info']; @endphp
              <span class="badge text-bg-{{ $map[$inv->status] ?? 'secondary' }}">{{ ucfirst($inv->status) }}</span>
            </td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.invoices.show', $inv->id) }}">{{ __('Open') }}</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  {{-- Generate single --}}
  <div class="modal fade" id="singleModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.invoices.generate-single') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('Generate Invoice') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">{{ __('Student') }} <span class="text-danger">*</span></label>
          <select name="student_id" class="form-select js-select" required>
            <option value="">— select —</option>
            @foreach ($students as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ $s->student_id }})</option>@endforeach
          </select></div>
        <div class="col-md-6"><label class="form-label">{{ __('Academic Year') }} <span class="text-danger">*</span></label>
          <select name="academic_year_id" class="form-select" required>
            @foreach ($years as $y)<option value="{{ $y->id }}" @selected($y->is_current)>{{ $y->year }}</option>@endforeach
          </select></div>
        <div class="col-md-6"><label class="form-label">{{ __('Month') }} <span class="text-muted small">(optional)</span></label>
          <select name="month" class="form-select"><option value="">—</option>
            @for ($m = 1; $m <= 12; $m++)<option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>@endfor
          </select></div>
        <div class="col-md-6"><label class="form-label">{{ __('Discount') }} <span class="text-muted small">(optional)</span></label>
          <select name="discount_id" class="form-select"><option value="">— none —</option>
            @foreach ($discounts as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
          </select></div>
        <div class="col-md-6"><label class="form-label">{{ __('Due Date') }} <span class="text-danger">*</span></label>
          <input type="date" name="due_date" class="form-control" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Generate') }}</button></div>
    </form>
  </div></div></div>

  {{-- Bulk generate --}}
  <div class="modal fade" id="bulkModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.invoices.generate-bulk') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('Bulk Generate By Class') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-md-6"><label class="form-label">{{ __('Class') }} <span class="text-danger">*</span></label>
          <select name="class_id" class="form-select" required>
            <option value="">— select —</option>
            @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
          </select></div>
        <div class="col-md-6"><label class="form-label">{{ __('Academic Year') }} <span class="text-danger">*</span></label>
          <select name="academic_year_id" class="form-select" required>
            @foreach ($years as $y)<option value="{{ $y->id }}" @selected($y->is_current)>{{ $y->year }}</option>@endforeach
          </select></div>
        <div class="col-md-6"><label class="form-label">{{ __('Month') }} <span class="text-muted small">(optional)</span></label>
          <select name="month" class="form-select"><option value="">—</option>
            @for ($m = 1; $m <= 12; $m++)<option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>@endfor
          </select></div>
        <div class="col-md-6"><label class="form-label">{{ __('Discount') }} <span class="text-muted small">(optional)</span></label>
          <select name="discount_id" class="form-select"><option value="">— none —</option>
            @foreach ($discounts as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach
          </select></div>
        <div class="col-12"><label class="form-label">{{ __('Due Date') }} <span class="text-danger">*</span></label>
          <input type="date" name="due_date" class="form-control" required></div>
        <div class="col-12"><div class="alert alert-info py-2 mb-0 small">Generates one invoice per active student in the class who doesn't already have an open invoice for the period.</div></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Generate') }}</button></div>
    </form>
  </div></div></div>
@endsection
