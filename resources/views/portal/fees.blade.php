@extends('layouts.portal')
@section('title', __('Fees'))
@section('heading', 'Fees')
@section('content')

  <div class="row g-3 mb-3">
    <div class="col-sm-6 col-lg-4">
      <div class="card"><div class="card-body">
        <div class="text-muted small mb-1">{{ __('Outstanding balance') }}</div>
        <div class="h4 mb-0 {{ $outstanding > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($outstanding) }}</div>
      </div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">{{ __('Invoices') }}</div>
    <div class="card-body p-0">
      <table class="table align-middle mb-0">
        <thead class="table-light">
          <tr><th>{{ __('Invoice') }}</th><th>{{ __('Due date') }}</th><th class="text-end">{{ __('Amount') }}</th><th class="text-end">{{ __('Paid') }}</th><th class="text-center">{{ __('Status') }}</th><th class="text-end">{{ __('Pay') }}</th></tr>
        </thead>
        <tbody>
          @forelse($invoices as $inv)
            @php
              $badge = match($inv->status) {
                'paid' => 'text-bg-success', 'partial' => 'text-bg-warning',
                'waived' => 'text-bg-info', 'cancelled' => 'text-bg-secondary', default => 'text-bg-danger',
              };
              $balance = (float) $inv->amount_due - (float) $inv->amount_paid - (float) $inv->credit_applied;
              $payable = $balance > 0 && ! in_array($inv->status, ['paid', 'cancelled', 'waived'], true);
            @endphp
            <tr>
              <td class="fw-medium">{{ $inv->invoice_number }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($inv->due_date)->format('j M Y') }}</td>
              <td class="text-end">{{ number_format($inv->amount_due) }}</td>
              <td class="text-end">{{ number_format($inv->amount_paid) }}</td>
              <td class="text-center"><span class="badge {{ $badge }}">{{ ucfirst($inv->status) }}</span></td>
              <td class="text-end">
                @if($payable && count($payGateways))
                  <div class="d-flex gap-1 justify-content-end flex-wrap">
                    @foreach($payGateways as $gw)
                      <form method="POST" action="{{ route('portal.pay.initiate', ['student' => $student->id]) }}" onsubmit="return confirm('Pay {{ number_format($balance) }} for {{ $inv->invoice_number }} with {{ $gw['label'] }}?')">
                        @csrf
                        <input type="hidden" name="invoice_id" value="{{ $inv->id }}">
                        <input type="hidden" name="gateway" value="{{ $gw['key'] }}">
                        <button class="btn btn-sm btn-outline-primary"><i class="bi {{ $gw['icon'] }}"></i> {{ $gw['label'] }}</button>
                      </form>
                    @endforeach
                  </div>
                @elseif($payable)
                  <span class="text-muted small">{{ __('At office') }}</span>
                @else — @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No invoices yet.') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($invoices)<div class="mt-3">{{ $invoices->appends(['student' => $student->id])->links() }}</div>@endif

@endsection
