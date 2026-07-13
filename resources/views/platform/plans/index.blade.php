@extends('platform.layout')
@section('title', 'Plans')
@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item">Platform</li><li class="breadcrumb-item active">Plans</li></ol></nav>
      <h1 class="h4 mb-0">Plans</h1>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal"><i class="bi bi-plus-lg"></i> New plan</button>
  </div>

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Name</th><th>Slug</th><th class="text-end">Monthly</th><th class="text-end">Yearly</th><th class="text-end">Students</th><th class="text-end">Staff</th><th>Self-serve</th><th>Active</th><th class="text-end" data-orderable="false"></th></tr></thead>
      <tbody>
        @foreach ($plans as $p)
          <tr>
            <td class="fw-semibold">{{ $p->name }}</td>
            <td><code>{{ $p->slug }}</code></td>
            <td class="text-end">{{ $p->price_monthly !== null ? number_format((float)$p->price_monthly, 2) : 'Free' }}</td>
            <td class="text-end">{{ $p->price_yearly !== null ? number_format((float)$p->price_yearly, 2) : 'Free' }}</td>
            <td class="text-end">{{ $p->max_students ?? '∞' }}</td>
            <td class="text-end">{{ $p->max_staff ?? '∞' }}</td>
            <td>{!! $p->is_self_serve ? '<i class="bi bi-check-lg text-success"></i>' : '—' !!}</td>
            <td>{!! $p->is_active ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Off</span>' !!}</td>
            <td class="text-end"><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $p->id }}">Edit</button></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  {{-- Create --}}
  <div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="{{ route('platform.plans.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">New plan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">@include('platform.plans._fields', ['plan' => null])</div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create</button></div>
    </form>
  </div></div></div>

  {{-- Edit --}}
  @foreach ($plans as $p)
    <div class="modal fade" id="editModal{{ $p->id }}" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
      <form method="POST" action="{{ route('platform.plans.update', $p->id) }}">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Edit {{ $p->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">@include('platform.plans._fields', ['plan' => $p])</div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
      </form>
    </div></div></div>
  @endforeach
@endsection
