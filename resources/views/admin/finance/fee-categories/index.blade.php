@extends('layouts.admin')
@section('title', __('Fee Categories'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => __('Fee categories'),
    'crumbs' => [__('Finance'), __('Fee categories')],
    'action' => ['label' => __('New category'), 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Items') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($categories as $c)
          <tr>
            <td class="fw-semibold">{{ $c->name }}</td>
            <td><span class="badge text-bg-light border text-muted">{{ $c->items_count }}</span></td>
            <td><span class="badge {{ $c->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $c->is_active ? 'Active' : 'Inactive' }}</span></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $c->id }}">{{ __('Edit') }}</button>
              <form method="POST" action="{{ route('admin.fee-categories.destroy', $c->id) }}" class="d-inline" onsubmit="return confirm('Delete {{ $c->name }}?')">
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
    <form method="POST" action="{{ route('admin.fee-categories.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('New Category') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
        <input name="name" class="form-control" value="{{ old('name') }}" required>
        <div class="form-check mt-3"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="cActive" checked><label class="form-check-label" for="cActive">{{ __('Active') }}</label></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
    </form>
  </div></div></div>

  @foreach ($categories as $c)
    <div class="modal fade" id="editModal{{ $c->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.fee-categories.update', $c->id) }}">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">{{ __('Edit Category') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
          <input name="name" class="form-control" value="{{ $c->name }}" required>
          <div class="form-check mt-3"><input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="eActive{{ $c->id }}" @checked($c->is_active)><label class="form-check-label" for="eActive{{ $c->id }}">{{ __('Active') }}</label></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
      </form>
    </div></div></div>
  @endforeach
@endsection
