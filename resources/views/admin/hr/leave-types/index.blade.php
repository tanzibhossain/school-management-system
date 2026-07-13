@extends('layouts.admin')
@section('title', 'Leave types')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Leave types',
    'crumbs' => ['HR', 'Leave types'],
    'action' => ['label' => 'New type', 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Name</th><th>Applies to</th><th>Max days/yr</th><th>Attachment</th><th>Paid</th><th>Status</th><th class="text-end" data-orderable="false">Actions</th></tr></thead>
      <tbody>
        @foreach ($types as $t)
          <tr>
            <td class="fw-semibold">{{ $t->name }}</td>
            <td class="text-capitalize">{{ $t->applies_to }}</td>
            <td>{{ $t->max_days_per_year ?? '—' }}</td>
            <td>{!! $t->requires_attachment ? '<i class="bi bi-check-lg text-success"></i>' : '—' !!}</td>
            <td>{!! $t->is_paid ? '<i class="bi bi-check-lg text-success"></i>' : '—' !!}</td>
            <td><span class="badge {{ $t->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $t->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $t->id }}">Edit</button>
              @if ($t->is_active)
                <form method="POST" action="{{ route('admin.leave-types.destroy', $t->id) }}" class="d-inline" onsubmit="return confirm('Deactivate {{ $t->name }}?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Deactivate</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @include('admin.hr.leave-types._form', ['mode' => 'create'])
  @foreach ($types as $t)
    @include('admin.hr.leave-types._form', ['mode' => 'edit', 't' => $t])
  @endforeach
@endsection
