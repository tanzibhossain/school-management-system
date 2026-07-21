@extends('layouts.admin')
@section('title', __('Transport — drivers'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Drivers',
    'crumbs' => ['Transport', 'Drivers'],
    'action' => ['label' => 'Add driver', 'modal' => 'createModal'],
  ])
  @include('admin.modules.transport._tabs', ['active' => 'drivers'])

  @php $statuses = ['active' => 'success', 'on_leave' => 'warning', 'inactive' => 'secondary']; @endphp
  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Phone') }}</th><th>{{ __('License') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($drivers as $d)
          <tr>
            <td class="fw-semibold">{{ $d->name }}</td>
            <td>{{ $d->phone ?? '—' }}</td>
            <td>{{ $d->license_no ?? '—' }}</td>
            <td><span class="badge text-bg-{{ $statuses[$d->status] ?? 'secondary' }}">{{ ucfirst(str_replace('_', ' ', $d->status)) }}</span></td>
            <td class="text-end"><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $d->id }}">{{ __('Edit') }}</button></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @include('admin.modules.transport.drivers._form', ['mode' => 'create'])
  @foreach ($drivers as $d)
    @include('admin.modules.transport.drivers._form', ['mode' => 'edit', 'd' => $d])
  @endforeach
@endsection
