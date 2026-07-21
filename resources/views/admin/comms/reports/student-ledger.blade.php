@extends('layouts.admin')
@section('title', __('Student Ledger Report'))
@section('content')
  @include('admin.partials.page-header', ['title' => 'Student ledger', 'crumbs' => ['Reports', 'Student ledger']])

  <ul class="nav nav-pills mb-3">
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.fee-collection') }}">{{ __('Fee Collection') }}</a></li>
    <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.outstanding-dues') }}">{{ __('Outstanding Dues') }}</a></li>
    <li class="nav-item"><a class="nav-link active" href="{{ route('admin.reports.student-ledger') }}">{{ __('Student Ledger') }}</a></li>
  </ul>

  <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-sm-5"><label class="form-label small text-muted mb-1">{{ __('Student') }}</label>
      <select name="student_id" class="form-select form-select-sm js-select" required>
        <option value="">— select —</option>
        @foreach ($students as $s)<option value="{{ $s->id }}" @selected(($filters['student_id'] ?? null) == $s->id)>{{ $s->name }} ({{ $s->student_id }})</option>@endforeach
      </select></div>
    <div class="col-sm-3"><label class="form-label small text-muted mb-1">{{ __('From') }}</label>
      <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}"></div>
    <div class="col-sm-2"><label class="form-label small text-muted mb-1">To</label>
      <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}"></div>
    <div class="col-sm-2">
      <button class="btn btn-sm btn-primary w-100">{{ __('Run') }}</button>
    </div>
  </div></form>

  @if ($data && $student)
    <div class="d-flex justify-content-between align-items-center mb-2">
      <h2 class="h6 mb-0">{{ $student->name }} <span class="text-muted">({{ $student->student_id }})</span></h2>
      <a class="btn btn-sm btn-outline-danger" href="{{ route('admin.reports.student-ledger', array_filter(['student_id' => $student->id, 'date_from' => $filters['date_from'] ?? null, 'date_to' => $filters['date_to'] ?? null, 'format' => 'pdf'])) }}" target="_blank"><i class="bi bi-file-pdf"></i> {{ __('PDF') }}</a>
    </div>
    <div class="card"><div class="card-body">
      <table class="table table-hover align-middle w-100">
        <thead><tr><th>{{ __('Date') }}</th><th>{{ __('Type') }}</th><th>{{ __('Description') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead>
        <tbody>
          @foreach ($data['entries'] as $e)
            <tr>
              <td class="small">{{ \Illuminate\Support\Str::of($e['date'])->substr(0, 10) }}</td>
              <td><span class="badge text-bg-light border text-muted text-capitalize">{{ str_replace('_', ' ', $e['type']) }}</span></td>
              <td>{{ $e['description'] }}</td>
              <td class="text-end">{{ number_format((float) $e['amount'], 2) }} {{ $e['currency'] }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div></div>
  @else
    <div class="alert alert-info">{{ __('Select A Student To View Their Ledger.') }}</div>
  @endif
@endsection
