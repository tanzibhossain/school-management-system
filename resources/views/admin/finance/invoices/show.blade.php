@extends('layouts.admin')
@section('title', 'Invoice ' . $invoice->invoice_number)
@section('content')
  @php
    $map = ['paid'=>'success','partial'=>'warning','unpaid'=>'secondary','cancelled'=>'dark','waived'=>'info'];
    $remaining = max((float) $invoice->amount_due - (float) $invoice->amount_paid, 0);
    $open = ! in_array($invoice->status, ['paid', 'cancelled', 'waived']);
  @endphp

  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Home</a></li><li class="breadcrumb-item">Finance</li><li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}" class="text-decoration-none">Invoices</a></li><li class="breadcrumb-item active">{{ $invoice->invoice_number }}</li></ol></nav>
      <h1 class="h4 mb-0">Invoice {{ $invoice->invoice_number }} <span class="badge text-bg-{{ $map[$invoice->status] ?? 'secondary' }} align-middle">{{ ucfirst($invoice->status) }}</span></h1>
    </div>
    @if ($open)
      <div class="d-flex gap-2">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payModal"><i class="bi bi-cash"></i> Record payment</button>
        <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#waiveModal">Waive</button>
        <button class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#cancelModal">Cancel</button>
      </div>
    @endif
  </div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card mb-4"><div class="card-header">Line items</div><div class="card-body">
        <table class="table align-middle mb-0">
          <thead><tr><th>Item</th><th class="text-end">Amount</th><th class="text-end">Discount</th><th class="text-end">Net</th></tr></thead>
          <tbody>
            @foreach ($invoice->items as $li)
              <tr><td>{{ $li->name }}</td><td class="text-end">{{ number_format((float) $li->amount, 2) }}</td><td class="text-end">{{ number_format((float) $li->discount_amount, 2) }}</td><td class="text-end">{{ number_format((float) $li->net_amount, 2) }}</td></tr>
            @endforeach
          </tbody>
          <tfoot>
            <tr><th colspan="3" class="text-end">Credit applied</th><th class="text-end">{{ number_format((float) $invoice->credit_applied, 2) }}</th></tr>
            <tr><th colspan="3" class="text-end">Amount due</th><th class="text-end">{{ number_format((float) $invoice->amount_due, 2) }}</th></tr>
            <tr><th colspan="3" class="text-end">Paid</th><th class="text-end">{{ number_format((float) $invoice->amount_paid, 2) }}</th></tr>
            <tr class="table-light"><th colspan="3" class="text-end">Remaining</th><th class="text-end">{{ number_format($remaining, 2) }}</th></tr>
          </tfoot>
        </table>
      </div></div>

      <div class="card"><div class="card-header">Payments</div><div class="card-body">
        @if ($invoice->payments->isEmpty())
          <p class="text-muted mb-0">No payments recorded.</p>
        @else
          <table class="table align-middle mb-0">
            <thead><tr><th>Receipt</th><th>Method</th><th class="text-end">Amount</th><th>Date</th></tr></thead>
            <tbody>
              @foreach ($invoice->payments as $p)
                <tr><td><code>{{ $p->receipt_number }}</code></td><td class="text-capitalize">{{ str_replace('_', ' ', $p->method) }}</td><td class="text-end">{{ number_format((float) $p->amount, 2) }}</td><td>{{ optional($p->paid_at)->format('d M Y') }}</td></tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div></div>
    </div>

    <div class="col-lg-5">
      <div class="card"><div class="card-header">Details</div><div class="card-body">
        <dl class="row mb-0">
          <dt class="col-5 text-muted">Student</dt><dd class="col-7">{{ $invoice->student?->name ?? '—' }}</dd>
          <dt class="col-5 text-muted">Student ID</dt><dd class="col-7">{{ $invoice->student?->student_id ?? '—' }}</dd>
          <dt class="col-5 text-muted">Currency</dt><dd class="col-7">{{ $invoice->currency }}</dd>
          <dt class="col-5 text-muted">Month</dt><dd class="col-7">{{ $invoice->month ? \Carbon\Carbon::create()->month($invoice->month)->format('F') : '—' }}</dd>
          <dt class="col-5 text-muted">Due date</dt><dd class="col-7">{{ optional($invoice->due_date)->format('d M Y') }}</dd>
          @if ($invoice->note)<dt class="col-5 text-muted">Note</dt><dd class="col-7">{{ $invoice->note }}</dd>@endif
        </dl>
      </div></div>
    </div>
  </div>

  @if ($open)
    {{-- Record payment --}}
    <div class="modal fade" id="payModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.payments.store', $invoice->id) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">Record payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
          <div class="col-md-6"><label class="form-label">Amount <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ number_format($remaining, 2, '.', '') }}" required></div>
          <div class="col-md-6"><label class="form-label">Method <span class="text-danger">*</span></label>
            <select name="method" class="form-select" id="payMethod" required>
              <option value="cash">Cash</option>
              <option value="bank_transfer">Bank transfer</option>
              <option value="cheque">Cheque</option>
            </select></div>
          <div class="col-12 cheque-fields d-none">
            <div class="row g-2">
              <div class="col-md-6"><label class="form-label">Cheque number</label><input name="cheque_number" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Bank name</label><input name="bank_name" class="form-control"></div>
              <div class="col-md-6"><label class="form-label">Cheque date</label><input type="date" name="cheque_date" class="form-control"></div>
            </div>
          </div>
          <div class="col-12"><label class="form-label">Note</label><input name="note" class="form-control"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success">Record</button></div>
      </form>
    </div></div></div>

    {{-- Waive --}}
    <div class="modal fade" id="waiveModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.invoices.waive', $invoice->id) }}">
        @csrf @method('PATCH')
        <div class="modal-header"><h5 class="modal-title">Waive invoice</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><label class="form-label">Reason <span class="text-danger">*</span></label><textarea name="note" class="form-control" rows="2" required></textarea></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-info">Waive</button></div>
      </form>
    </div></div></div>

    {{-- Cancel --}}
    <div class="modal fade" id="cancelModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.invoices.cancel', $invoice->id) }}">
        @csrf @method('PATCH')
        <div class="modal-header"><h5 class="modal-title">Cancel invoice</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><label class="form-label">Reason <span class="text-danger">*</span></label><textarea name="note" class="form-control" rows="2" required></textarea></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep</button><button class="btn btn-dark">Cancel invoice</button></div>
      </form>
    </div></div></div>
  @endif

  @push('scripts')
    <script>
      (function () {
        var m = document.getElementById('payMethod');
        if (!m) return;
        var cf = document.querySelector('.cheque-fields');
        m.addEventListener('change', function () { cf.classList.toggle('d-none', m.value !== 'cheque'); });
      })();
    </script>
  @endpush
@endsection
