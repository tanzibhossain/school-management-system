@extends('layouts.admin')
@section('title', __('Payroll — Components'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Salary components',
    'crumbs' => ['Payroll', 'Components'],
    'action' => ['label' => 'New component', 'modal' => 'createModal'],
  ])
  @include('admin.modules.payroll._tabs', ['active' => 'components'])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Type') }}</th><th>{{ __('Default') }}</th><th>{{ __('Order') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($components as $c)
          <tr>
            <td class="fw-semibold">{{ $c->name }}</td>
            <td><span class="badge text-bg-{{ $c->component_type === 'earning' ? 'success' : 'danger' }}">{{ ucfirst($c->component_type) }}</span></td>
            <td>{!! $c->is_default ? '<i class="bi bi-check-lg text-success"></i>' : '—' !!}</td>
            <td>{{ $c->sort_order }}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $c->id }}">{{ __('Edit') }}</button>
              <form method="POST" action="{{ route('admin.payroll.components.destroy', $c->id) }}" class="d-inline" onsubmit="return confirm('Remove {{ $c->name }}?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">{{ __('Remove') }}</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @include('admin.modules.payroll.components._form', ['mode' => 'create'])
  @foreach ($components as $c)
    @include('admin.modules.payroll.components._form', ['mode' => 'edit', 'c' => $c])
  @endforeach
@endsection
