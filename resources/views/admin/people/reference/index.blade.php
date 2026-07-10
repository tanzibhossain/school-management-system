@extends('layouts.admin')
@section('title', $label)
@section('content')
  @include('admin.partials.page-header', [
    'title'  => $label,
    'crumbs' => ['People', $label],
    'action' => ['label' => 'New ' . \Illuminate\Support\Str::lower($singular), 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Name</th><th>Staff</th><th class="text-end" data-orderable="false">Actions</th></tr></thead>
      <tbody>
        @foreach ($items as $item)
          <tr>
            <td class="fw-semibold">{{ $item->name }}</td>
            <td><span class="badge text-bg-light border text-muted">{{ $item->staff_count }}</span></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $item->id }}">Edit</button>
              <form method="POST" action="{{ route('admin.' . $type . '.destroy', $item->id) }}" class="d-inline" onsubmit="return confirm('Delete {{ $item->name }}?')">
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
    <form method="POST" action="{{ route('admin.' . $type . '.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">New {{ \Illuminate\Support\Str::lower($singular) }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ old('name') }}" required>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
    </form>
  </div></div></div>

  @foreach ($items as $item)
    <div class="modal fade" id="editModal{{ $item->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.' . $type . '.update', $item->id) }}">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">Edit {{ \Illuminate\Support\Str::lower($singular) }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input name="name" class="form-control" value="{{ $item->name }}" required>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save</button></div>
      </form>
    </div></div></div>
  @endforeach
@endsection
