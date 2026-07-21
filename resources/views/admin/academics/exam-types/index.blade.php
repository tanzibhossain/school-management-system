@extends('layouts.admin')
@section('title', __('Exam types'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Exam types',
    'crumbs' => ['Academics', 'Exam types'],
    'action' => ['label' => 'New type', 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Description') }}</th><th>{{ __('Exams') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($types as $t)
          <tr>
            <td class="fw-semibold">{{ $t->name }}</td>
            <td>{{ $t->description ?? '—' }}</td>
            <td><span class="badge text-bg-light border text-muted">{{ $t->exams_count }}</span></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $t->id }}">{{ __('Edit') }}</button>
              <form method="POST" action="{{ route('admin.exam-types.destroy', $t->id) }}" class="d-inline" onsubmit="return confirm('Delete {{ $t->name }}?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.exam-types.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('New exam type') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
          <input name="name" class="form-control" value="{{ old('name') }}" placeholder="{{ __('e.g. Midterm') }}" required></div>
        <div><label class="form-label">{{ __('Description') }}</label>
          <input name="description" class="form-control" value="{{ old('description') }}"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
    </form>
  </div></div></div>

  @foreach ($types as $t)
    <div class="modal fade" id="editModal{{ $t->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.exam-types.update', $t->id) }}">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">{{ __('Edit exam type') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
            <input name="name" class="form-control" value="{{ $t->name }}" required></div>
          <div><label class="form-label">{{ __('Description') }}</label>
            <input name="description" class="form-control" value="{{ $t->description }}"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
      </form>
    </div></div></div>
  @endforeach
@endsection
