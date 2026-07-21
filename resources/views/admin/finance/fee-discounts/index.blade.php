@extends('layouts.admin')
@section('title', __('Discounts'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Discounts',
    'crumbs' => ['Finance', 'Discounts'],
    'action' => ['label' => 'New discount', 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Type') }}</th><th>{{ __('Value') }}</th><th>{{ __('Max amount') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($discounts as $d)
          <tr>
            <td class="fw-semibold">{{ $d->name }}</td>
            <td class="text-capitalize">{{ $d->type }}</td>
            <td>{{ $d->type === 'percentage' ? rtrim(rtrim(number_format((float) $d->value, 2), '0'), '.') . '%' : number_format((float) $d->value, 2) }}</td>
            <td>{{ $d->max_amount ? number_format((float) $d->max_amount, 2) : '—' }}</td>
            <td><span class="badge {{ $d->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $d->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $d->id }}">{{ __('Edit') }}</button>
              @if ($d->is_active)
                <form method="POST" action="{{ route('admin.fee-discounts.deactivate', $d->id) }}" class="d-inline" onsubmit="return confirm('Deactivate {{ $d->name }}?')">
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

  @include('admin.finance.fee-discounts._form', ['mode' => 'create'])
  @foreach ($discounts as $d)
    @include('admin.finance.fee-discounts._form', ['mode' => 'edit', 'd' => $d])
  @endforeach
@endsection
