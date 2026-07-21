@extends('layouts.admin')
@section('title', __('Outstanding dues report'))
@section('content')
  @include('admin.partials.page-header', ['title' => 'Outstanding dues', 'crumbs' => ['Reports', 'Outstanding dues']])

  <ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.fee-collection') }}">{{ __('Fee collection') }}</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.reports.outstanding-dues') }}">{{ __('Outstanding dues') }}</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.student-ledger') }}">{{ __('Student ledger') }}</a></li>
  </ul>

  <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-sm-4"><label class="form-label small text-muted mb-1">{{ __('Class') }}</label>
      <select name="class_id" class="form-select form-select-sm">
        <option value="">{{ __('All classes') }}</option>
        @foreach ($classes as $c)<option value="{{ $c->id }}" @selected($classId == $c->id)>{{ $c->name }}</option>@endforeach
      </select></div>
    <div class="col-sm-8">
      <button class="btn btn-sm btn-primary">{{ __('Filter') }}</button>
      <a class="btn btn-sm btn-outline-danger" href="{{ route('admin.reports.outstanding-dues', array_filter(['class_id' => $classId, 'format' => 'pdf'])) }}" target="_blank"><i class="bi bi-file-pdf"></i> {{ __('PDF') }}</a>
    </div>
  </div></form>

  <div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Students with dues') }}</div><div class="h4 mb-0">{{ $data['summary']['student_count'] }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Open invoices') }}</div><div class="h4 mb-0">{{ $data['summary']['invoice_count'] }}</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Total due') }}</div>
      @foreach ($data['summary']['totals_by_currency'] as $cur => $amt)<span class="badge text-bg-danger me-1">{{ number_format((float) $amt, 2) }} {{ $cur }}</span>@endforeach
    </div></div></div>
  </div>

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Class') }}</th><th>{{ __('Invoices') }}</th><th>{{ __('Oldest due') }}</th><th class="text-end">{{ __('Total due') }}</th></tr></thead>
      <tbody>
        @foreach ($data['students'] as $s)
          <tr>
            <td class="fw-semibold">{{ $s['student_name'] }}</td>
            <td>{{ $s['class_name'] ?? '—' }}</td>
            <td>{{ $s['invoice_count'] }}</td>
            <td class="small">{{ \Illuminate\Support\Str::of($s['oldest_due_date'])->substr(0, 10) }}</td>
            <td class="text-end">{{ number_format((float) $s['total_due'], 2) }} {{ $s['currency'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>
@endsection
