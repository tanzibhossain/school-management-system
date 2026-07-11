@extends('layouts.admin')
@section('title', 'Transport — routes')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Routes',
    'crumbs' => ['Transport', 'Routes'],
    'action' => ['label' => 'New route', 'modal' => 'createModal'],
  ])
  @include('admin.modules.transport._tabs', ['active' => 'routes'])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Name</th><th>Fare</th><th>Vehicle</th><th>Driver</th><th>Riders</th><th class="text-end" data-orderable="false">Actions</th></tr></thead>
      <tbody>
        @foreach ($routes as $r)
          <tr>
            <td class="fw-semibold">{{ $r->name }}</td>
            <td>{{ number_format((float) $r->fare, 2) }}</td>
            <td>{{ $r->vehicle?->registration_no ?? '—' }}</td>
            <td>{{ $r->driver?->name ?? '—' }}</td>
            <td><span class="badge text-bg-light border text-muted">{{ $r->riders_count }}</span></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.transport.routes.show', $r->id) }}">Open</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.transport.routes.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">New route</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">Name <span class="text-danger">*</span></label>
          <input name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Route A — Uptown" required></div>
        <div class="col-md-6"><label class="form-label">Fare</label>
          <input type="number" step="0.01" min="0" name="fare" class="form-control" value="{{ old('fare', 0) }}"></div>
        <div class="col-md-6"><label class="form-label">Driver</label>
          <select name="driver_id" class="form-select"><option value="">— none —</option>
            @foreach ($drivers as $d)<option value="{{ $d->id }}" @selected(old('driver_id')==$d->id)>{{ $d->name }}</option>@endforeach
          </select></div>
        <div class="col-12"><label class="form-label">Description</label>
          <input name="description" class="form-control" value="{{ old('description') }}"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create</button></div>
    </form>
  </div></div></div>
@endsection
