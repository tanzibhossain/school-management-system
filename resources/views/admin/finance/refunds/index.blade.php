@extends('layouts.admin')
@section('title', __('Refunds'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => __('Refunds'),
    'crumbs' => [__('Finance'), __('Refunds')],
    'action' => ['label' => __('Request refund'), 'modal' => 'requestModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Payment') }}</th><th>{{ __('Method') }}</th><th class="text-end">{{ __('Amount') }}</th><th class="text-end">{{ __('Fee') }}</th><th class="text-end">{{ __('Net') }}</th><th>{{ __('Status') }}</th></tr></thead>
      <tbody>
        @foreach ($refunds as $r)
          <tr>
            <td><code>{{ $r->payment?->receipt_number ?? '—' }}</code></td>
            <td class="text-capitalize">{{ str_replace('_', ' ', $r->method) }}</td>
            <td class="text-end">{{ number_format((float) $r->amount, 2) }}</td>
            <td class="text-end">{{ number_format((float) $r->processing_fee, 2) }}</td>
            <td class="text-end">{{ number_format((float) $r->net_refund, 2) }}</td>
            <td>
              @php $m = ['pending'=>'warning','completed'=>'success','failed'=>'danger']; @endphp
              <span class="badge text-bg-{{ $m[$r->status] ?? 'secondary' }}">{{ ucfirst($r->status) }}</span>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="requestModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.refunds.store') }}" id="refundForm">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('Request Refund') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">{{ __('Payment') }} <span class="text-danger">*</span></label>
          <select name="payment_id" class="form-select js-select" id="refundPayment" required>
            <option value="">— select —</option>
            @foreach ($payments as $p)
              <option value="{{ $p->id }}" data-amount="{{ $p->amount }}">{{ $p->receipt_number }} — {{ $p->invoice?->invoice_number }} ({{ number_format((float) $p->amount, 2) }})</option>
            @endforeach
          </select></div>
        <div class="col-md-6"><label class="form-label">{{ __('Amount') }} <span class="text-danger">*</span></label>
          <input type="number" step="0.01" min="0.01" name="amount" id="refundAmount" class="form-control" required></div>
        <div class="col-12"><label class="form-label">{{ __('Note') }}</label><input name="note" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Request') }}</button></div>
    </form>
  </div></div></div>

  @push('scripts')
    <script>
      (function () {
        var sel = document.getElementById('refundPayment');
        var form = document.getElementById('refundForm');
        var amt = document.getElementById('refundAmount');
        if (!sel) return;
        function sync() {
          var opt = sel.options[sel.selectedIndex];
          var max = opt ? opt.getAttribute('data-amount') : '';
          if (max) { amt.max = max; amt.value = max; }
        }
        sel.addEventListener('change', sync);
      })();
    </script>
  @endpush
@endsection
