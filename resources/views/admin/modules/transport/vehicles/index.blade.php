@extends('layouts.admin')
@section('title', __('Transport — vehicles'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Vehicles',
    'crumbs' => ['Transport', 'Vehicles'],
    'action' => ['label' => 'Add vehicle', 'modal' => 'createModal'],
  ])
  @include('admin.modules.transport._tabs', ['active' => 'vehicles'])

  @php $statuses = ['available' => 'success', 'in_service' => 'primary', 'out_of_service' => 'secondary']; @endphp
  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Registration') }}</th><th>{{ __('Capacity') }}</th><th>{{ __('Status') }}</th><th>{{ __('Notes') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($vehicles as $v)
          <tr>
            <td class="fw-semibold"><code>{{ $v->registration_no }}</code></td>
            <td>{{ $v->capacity }}</td>
            <td><span class="badge text-bg-{{ $statuses[$v->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $v->status)) }}</span></td>
            <td>{{ $v->notes ?? '—' }}</td>
            <td class="text-end"><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $v->id }}">{{ __('Edit') }}</button></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @include('admin.modules.transport.vehicles._form', ['mode' => 'create'])
  @foreach ($vehicles as $v)
    @include('admin.modules.transport.vehicles._form', ['mode' => 'edit', 'v' => $v])
  @endforeach
@endsection
