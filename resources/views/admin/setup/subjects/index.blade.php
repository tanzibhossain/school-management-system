@extends('layouts.admin')
@section('title', 'Subjects')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Subjects',
    'crumbs' => ['Setup', 'Subjects'],
    'action' => ['label' => 'New subject', 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Name</th><th>Code</th><th class="text-end" data-orderable="false">Actions</th></tr></thead>
      <tbody>
        @foreach ($subjects as $s)
          <tr>
            <td class="fw-semibold">{{ $s->name }}</td>
            <td>{{ $s->sub_code ?? '—' }}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $s->id }}">Edit</button>
              <form method="POST" action="{{ route('admin.subjects.destroy', $s->id) }}" class="d-inline" onsubmit="return confirm('Delete {{ $s->name }}?')">
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
    <form method="POST" action="{{ route('admin.subjects.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">New subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">Name <span class="text-danger">*</span></label>
          <input name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Mathematics" required></div>
        <div><label class="form-label">Subject code</label>
          <input name="sub_code" class="form-control" value="{{ old('sub_code') }}" placeholder="e.g. MATH-101"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
    </form>
  </div></div></div>

  @foreach ($subjects as $s)
    <div class="modal fade" id="editModal{{ $s->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.subjects.update', $s->id) }}">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Edit subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">Name <span class="text-danger">*</span></label>
            <input name="name" class="form-control" value="{{ $s->name }}" required></div>
          <div><label class="form-label">Subject code</label>
            <input name="sub_code" class="form-control" value="{{ $s->sub_code }}"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
      </form>
    </div></div></div>
  @endforeach
@endsection
