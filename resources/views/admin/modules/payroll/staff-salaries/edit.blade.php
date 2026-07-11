@extends('layouts.admin')
@section('title', 'Salary — ' . $staff->name)
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Salary structure — ' . $staff->name,
    'crumbs' => ['Payroll', 'Staff salaries', $staff->name],
  ])
  <div class="mb-3"><a href="{{ route('admin.payroll.staff-salaries.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Back to staff salaries</a></div>

  @if ($breakdown->isEmpty())
    <div class="alert alert-warning">No salary components defined yet. Add some under the <a href="{{ route('admin.payroll.components.index') }}">Components</a> tab first.</div>
  @else
    <form method="POST" action="{{ route('admin.payroll.staff-salaries.update', $staff->id) }}">
      @csrf @method('PUT')
      <div class="card"><div class="card-body">
        <table class="table align-middle">
          <thead><tr><th>Component</th><th>Type</th><th style="width:220px" class="text-end">Amount</th></tr></thead>
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
        <div class="text-end"><button class="btn btn-primary"><i class="bi bi-save"></i> Save salary</button></div>
      </div></div>
    </form>
  @endif
@endsection
