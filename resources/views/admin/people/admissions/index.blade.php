@extends('layouts.admin')
@section('title', __('Admissions'))
@section('content')
  @include('admin.partials.page-header', ['title' => 'Admission applications', 'crumbs' => ['People', 'Admissions']])

  <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-sm-4"><label class="form-label small text-muted mb-1">{{ __('Status') }}</label>
      <select name="status" class="form-select form-select-sm">
        @foreach (['' => 'All', 'submitted' => 'Submitted', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $v => $l)
          <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
        @endforeach
      </select></div>
    <div class="col-sm-8"><button class="btn btn-sm btn-outline-primary">{{ __('Filter') }}</button>
      <a href="{{ route('admin.admissions.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Reset') }}</a></div>
  </div></form>

  @php $m = ['submitted'=>'warning','approved'=>'success','rejected'=>'danger']; @endphp
  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Reference') }}</th><th>{{ __('Applicant') }}</th><th>{{ __('Guardian') }}</th><th>{{ __('Phone') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($applications as $a)
          <tr>
            <td><code>{{ $a->reference_number }}</code></td>
            <td class="fw-semibold">{{ $a->applicant_name }}</td>
            <td>{{ $a->guardian_name }}</td>
            <td>{{ $a->guardian_phone }}</td>
            <td><span class="badge text-bg-{{ $m[$a->status] ?? 'secondary' }}">{{ ucfirst($a->status) }}</span></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.admissions.show', $a->id) }}">{{ __('Review') }}</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>
@endsection
