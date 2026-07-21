@extends('layouts.admin')
@section('title', __('Fee items'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Fee items',
    'crumbs' => ['Finance', 'Fee items'],
    'action' => ['label' => 'New fee item', 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Category') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Frequency') }}</th><th>{{ __('Mandatory') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($items as $it)
          <tr>
            <td class="fw-semibold">{{ $it->name }}</td>
            <td>{{ $it->category?->name ?? '—' }}</td>
            <td>{{ number_format((float) $it->amount, 2) }}</td>
            <td><span class="text-capitalize">{{ str_replace('_', ' ', $it->frequency) }}</span></td>
            <td>{!! $it->is_mandatory ? '<i class="bi bi-check-lg text-success"></i>' : '—' !!}</td>
            <td><span class="badge {{ $it->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $it->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $it->id }}">{{ __('Edit') }}</button>
              @if ($it->is_active)
                <form method="POST" action="{{ route('admin.fee-items.deactivate', $it->id) }}" class="d-inline" onsubmit="return confirm('Deactivate {{ $it->name }}?')">
                  @csrf @method('PATCH')
                  <button class="btn btn-sm btn-outline-danger">{{ __('Deactivate') }}</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @include('admin.finance.fee-items._form', ['mode' => 'create'])
  @foreach ($items as $it)
    @include('admin.finance.fee-items._form', ['mode' => 'edit', 'it' => $it])
  @endforeach
@endsection
