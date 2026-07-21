@extends('layouts.admin')
@section('title', __('Fee Collection Report'))
@section('content')
  @include('admin.partials.page-header', ['title' => 'Fee collection', 'crumbs' => ['Reports', 'Fee collection']])

  <ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.reports.fee-collection') }}">{{ __('Fee Collection') }}</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.outstanding-dues') }}">{{ __('Outstanding Dues') }}</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.student-ledger') }}">{{ __('Student Ledger') }}</a></li>
  </ul>

  <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-sm-3"><label class="form-label small text-muted mb-1">{{ __('From') }}</label>
      <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $from }}"></div>
    <div class="col-sm-3"><label class="form-label small text-muted mb-1">To</label>
      <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $to }}"></div>
    <div class="col-sm-6">
      <button class="btn btn-sm btn-primary" name="run" value="1">{{ __('Run Report') }}</button>
      @if ($data)<a class="btn btn-sm btn-outline-danger" href="{{ route('admin.reports.fee-collection', ['date_from' => $from, 'date_to' => $to, 'format' => 'pdf']) }}" target="_blank"><i class="bi bi-file-pdf"></i> {{ __('PDF') }}</a>@endif
    </div>
  </div></form>

  @if ($data)
    <div class="row g-3 mb-3">
      <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Payments') }}</div><div class="h4 mb-0">{{ $data['summary']['count'] }}</div></div></div></div>
      <div class="col-md-8"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Totals By Currency') }}</div>
        @foreach ($data['summary']['totals_by_currency'] as $cur => $amt)<span class="badge text-bg-success me-1">{{ number_format((float) $amt, 2) }} {{ $cur }}</span>@endforeach
      </div></div></div>
    </div>
    <div class="card"><div class="card-body">
      <table class="table table-hover align-middle w-100 js-dt">
        <thead><tr><th>{{ __('Receipt') }}</th><th>{{ __('Student') }}</th><th>{{ __('Class') }}</th><th>{{ __('Method') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('Date') }}</th></tr></thead>
        <tbody>
          @foreach ($data['payments'] as $p)
            <tr>
              <td><code>{{ $p['receipt_number'] }}</code></td>
              <td>{{ $p['student_name'] }}</td>
              <td>{{ $p['class_name'] ?? '—' }}</td>
              <td class="text-capitalize">{{ str_replace('_', ' ', $p['method']) }}</td>
              <td class="text-end">{{ number_format((float) $p['amount'], 2) }} {{ $p['currency'] }}</td>
              <td class="small">{{ \Illuminate\Support\Str::of($p['paid_at'])->substr(0, 10) }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div></div>
  @else
    <div class="alert alert-info">{{ __('Pick A Date Range And Run The Report.') }}</div>
  @endif
@endsection
