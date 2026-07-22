@extends('layouts.admin')
@section('title', __('Payments'))
@section('content')
  @include('admin.partials.page-header', ['title' => __('Payments'), 'crumbs' => [__('Finance'), __('Payments')]])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Receipt') }}</th><th>{{ __('Invoice') }}</th><th>{{ __('Student') }}</th><th>{{ __('Method') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Date') }}</th></tr></thead>
      <tbody>
        @foreach ($payments as $p)
          <tr>
            <td><code>{{ $p->receipt_number }}</code></td>
            <td>@if ($p->invoice)<a href="{{ route('admin.invoices.show', $p->invoice->id) }}" class="text-decoration-none">{{ $p->invoice->invoice_number }}</a>@else — @endif</td>
            <td>{{ $p->invoice?->student?->name ?? '—' }}</td>
            <td class="text-capitalize">{{ str_replace('_', ' ', $p->method) }}</td>
            <td class="text-end">{{ number_format((float) $p->amount, 2) }} {{ $p->currency }}</td>
            <td>{{ optional($p->paid_at)->format('d M Y') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>
@endsection
