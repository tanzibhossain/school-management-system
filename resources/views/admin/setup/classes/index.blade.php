@extends('layouts.admin')
@section('title', 'Classes & sections')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Classes & sections',
    'crumbs' => ['Setup', 'Classes'],
    'action' => ['label' => 'New class', 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Class</th><th>Sections</th><th class="text-end" data-orderable="false">Actions</th></tr></thead>
      <tbody>
        @foreach ($classes as $class)
          <tr>
            <td class="fw-semibold">{{ $class->name }}</td>
            <td><span class="badge text-bg-light border text-muted">{{ $class->sections_count }}</span></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.classes.sections.index', $class->id) }}">Manage sections</a>
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $class->id }}">Edit</button>
              <form method="POST" action="{{ route('admin.classes.destroy', $class->id) }}" class="d-inline" onsubmit="return confirm('Delete {{ $class->name }}?')">
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
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Class 6" required>
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
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input name="name" class="form-control" value="{{ $class->name }}" required>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
      </form>
    </div></div></div>
  @endforeach
@endsection
