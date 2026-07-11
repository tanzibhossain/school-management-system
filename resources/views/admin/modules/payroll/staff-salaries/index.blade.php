@extends('layouts.admin')
@section('title', 'Payroll — staff salaries')
@section('content')
  @include('admin.partials.page-header', ['title' => 'Staff salaries', 'crumbs' => ['Payroll', 'Staff salaries']])
  @include('admin.modules.payroll._tabs', ['active' => 'salaries'])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Employee</th><th>Name</th><th class="text-end">Gross</th><th class="text-end">Deductions</th><th class="text-end">Net</th><th class="text-end" data-orderable="false">Actions</th></tr></thead>
      <tbody>
        @foreach ($staff as $s)
          @php $sum = $summary[$s->id]; @endphp
          <tr>
            <td><code>{{ $s->employee_id }}</code></td>
            <td class="fw-semibold">{{ $s->name }}</td>
            <td class="text-end">{{ number_format($sum['gross'], 2) }}</td>
            <td class="text-end">{{ number_format($sum['deductions'], 2) }}</td>
            <td class="text-end fw-semibold">{{ number_format($sum['net'], 2) }}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.payroll.staff-salaries.edit', $s->id) }}">Set salary</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>
@endsection
