@extends('layouts.admin')
@section('title', 'Classes')
@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Classes</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">New class</button>
  </div>

  <div class="card"><div class="card-body">
    <table id="classesTable" class="table table-hover align-middle w-100">
      <thead><tr><th>Name</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        @foreach ($classes as $class)
          <tr>
            <td>{{ $class->name }}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $class->id }}">Edit</button>
              <form method="POST" action="{{ route('admin.classes.destroy', $class->id) }}" class="d-inline" onsubmit="return confirm('Delete this class?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.classes.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">New class</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" placeholder="e.g. Class 6" required>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
    </form>
  </div></div></div>

  @foreach ($classes as $class)
    <div class="modal fade" id="editModal{{ $class->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.classes.update', $class->id) }}">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Edit class</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <label class="form-label">Name</label>
          <input name="name" class="form-control" value="{{ $class->name }}" required>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
      </form>
    </div></div></div>
  @endforeach

  @push('scripts')
    <script>$(function () { $('#classesTable').DataTable({ pageLength: 25, order: [[0, 'asc']], columnDefs: [{ orderable: false, targets: 1 }] }); });</script>
  @endpush
@endsection
