@extends('layouts.admin')
@section('title', 'Fee collection report')
@section('content')
  @include('admin.partials.page-header', ['title' => 'Fee collection', 'crumbs' => ['Reports', 'Fee collection']])

  <ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.reports.fee-collection') }}">Fee collection</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.outstanding-dues') }}">Outstanding dues</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.student-ledger') }}">Student ledger</a></li>
  </ul>

  <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-sm-3"><label class="form-label small text-muted mb-1">From</label>
      <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $from }}"></div>
    <div class="col-sm-3"><label class="form-label small text-muted mb-1">To</label>
      <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $to }}"></div>
    <div class="col-sm-6">
      <button class="btn btn-sm btn-primary" name="run" value="1">Run report</button>
      @if ($data)<a class="btn btn-sm btn-outline-danger" href="{{ route('admin.reports.fee-collection', ['date_from' => $from, 'date_to' => $to, 'format' => 'pdf']) }}" target="_blank"><i class="bi bi-file-pdf"></i> PDF</a>@endif
    </div>
  </div></form>

  @if ($data)
    <div class="row g-3 mb-3">
      <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">Payments</div><div class="h4 mb-0">{{ $data['summary']['count'] }}</div></div></div></div>
      <div class="col-md-8"><div class="card"><div class="card-body"><div class="text-muted small">Totals by currency</div>
        @foreach ($data['summary']['totals_by_currency'] as $cur => $amt)<span class="badge text-bg-success me-1">{{ number_format((float) $amt, 2) }} {{ $cur }}</span>@endforeach
      </div></div></div>
    </div>
    <div class="card"><div class="card-body">
      <table class="table table-hover align-middle w-100 js-dt">
        <thead><tr><th>Receipt</th><th>Student</th><th>Class</th><th>Method</th><th class="text-end">Amount</th><th>Date</th></tr></thead>
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
    <div class="alert alert-info">Pick a date range and run the report.</div>
  @endif
@endsection
