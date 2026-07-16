@extends('layouts.portal')
@section('title', 'Fees')
@section('heading', 'Fees')
@section('content')

  <div class="row g-3 mb-3">
    <div class="col-sm-6 col-lg-4">
      <div class="card"><div class="card-body">
        <div class="text-muted small mb-1">Outstanding balance</div>
        <div class="h4 mb-0 {{ $outstanding > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($outstanding) }}</div>
      </div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Invoices</div>
    <div class="card-body p-0">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr><th>Invoice</th><th>Due date</th><th class="text-end">Amount</th><th class="text-end">Paid</th><th class="text-center">Status</th></tr>
        </thead>
        <tbody>
          @forelse($invoices as $inv)
            @php
              $badge = match($inv->status) {
                'paid' => 'text-bg-success', 'partial' => 'text-bg-warning',
                'waived' => 'text-bg-info', 'cancelled' => 'text-bg-secondary', default => 'text-bg-danger',
              };
            @endphp
            <tr>
              <td class="fw-medium">{{ $inv->invoice_number }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($inv->due_date)->format('j M Y') }}</td>
              <td class="text-end">{{ number_format($inv->amount_due) }}</td>
              <td class="text-end">{{ number_format($inv->amount_paid) }}</td>
              <td class="text-center"><span class="badge {{ $badge }}">{{ ucfirst($inv->status) }}</span></td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No invoices yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($invoices)<div class="mt-3">{{ $invoices->appends(['student' => $student->id])->links() }}</div>@endif

@endsection
