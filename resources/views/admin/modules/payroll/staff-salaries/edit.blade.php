@extends('layouts.admin')
@section('title', 'Salary — ' . $staff->name)
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Salary structure — ' . $staff->name,
    'crumbs' => [__('Payroll'), __('Staff salaries'), $staff->name],
  ])
  <div class="mb-3"><a href="{{ route('admin.payroll.staff-salaries.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> {{ __('Back To Staff Salaries') }}</a></div>

  @if ($breakdown->isEmpty())
    <div class="alert alert-warning">{{ __('No Salary Components Defined Yet. Add Some Under The') }} <a href="{{ route('admin.payroll.components.index') }}">{{ __('Components') }}</a> {{ __('Tab First.') }}</div>
  @else
    <form method="POST" action="{{ route('admin.payroll.staff-salaries.update', $staff->id) }}">
      @csrf @method('PUT')
      <div class="card"><div class="card-body">
        <table class="table align-middle">
          <thead><tr><th>{{ __('Component') }}</th><th>{{ __('Type') }}</th><th style="width:220px" class="text-end">{{ __('Amount') }}</th></tr></thead>
          <tbody>
            @foreach ($breakdown as $row)
              <tr>
                <td class="fw-semibold">{{ $row['component']->name }}</td>
                <td><span class="badge text-bg-{{ $row['component']->component_type === 'earning' ? 'success' : 'danger' }}">{{ ucfirst($row['component']->component_type) }}</span></td>
                <td><input type="number" step="0.01" min="0" name="amounts[{{ $row['component']->id }}]" class="form-control form-control-sm text-end" value="{{ $row['amount'] }}"></td>
              </tr>
            @endforeach
          </tbody>
        </table>
        <div class="text-end"><button class="btn btn-primary"><i class="bi bi-save"></i> {{ __('Save Salary') }}</button></div>
      </div></div>
    </form>
  @endif
@endsection
