@extends('layouts.admin')
@section('title', __('Exam Halls'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Exam halls',
    'crumbs' => ['Academics', 'Exam halls'],
    'action' => ['label' => 'New hall', 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Name') }}</th><th>{{ __('Description') }}</th><th>{{ __('Seats') }}</th><th>{{ __('Available') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($halls as $h)
          <tr>
            <td class="fw-semibold">{{ $h->name }}</td>
            <td>{{ $h->description ?? '—' }}</td>
            <td>{{ $h->seats_count }}</td>
            <td><span class="badge text-bg-success">{{ $h->available_count }}</span></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.exam-halls.show', $h->id) }}">{{ __('Seats') }}</a>
              <form method="POST" action="{{ route('admin.exam-halls.destroy', $h->id) }}" class="d-inline" onsubmit="return confirm('Delete {{ $h->name }}?')">
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
    <form method="POST" action="{{ route('admin.exam-halls.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('New Exam Hall') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
          <input name="name" class="form-control" value="{{ old('name') }}" placeholder="{{ __('E.g. Main Hall') }}" required></div>
        <div class="col-12"><label class="form-label">{{ __('Description') }}</label>
          <input name="description" class="form-control" value="{{ old('description') }}"></div>
        <div class="col-md-4"><label class="form-label">{{ __('Rows') }} <span class="text-danger">*</span></label>
          <input type="number" min="1" max="100" name="rows" class="form-control" value="{{ old('rows', 10) }}" required></div>
        <div class="col-md-4"><label class="form-label">{{ __('Left Seats/row') }} <span class="text-danger">*</span></label>
          <input type="number" min="1" max="20" name="left_per_row" class="form-control" value="{{ old('left_per_row', 3) }}" required></div>
        <div class="col-md-4"><label class="form-label">{{ __('Right Seats/row') }}</label>
          <input type="number" min="0" max="20" name="right_per_row" class="form-control" value="{{ old('right_per_row', 3) }}"></div>
        <div class="col-12"><div class="alert alert-info py-2 mb-0 small">{{ __('Seats Are Generated As') }} <code>R01-L1, R01-L2, … R01-R1 …</code> {{ __('Per Row. You Can Block Individual Seats Afterwards.') }}</div></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Create') }}</button></div>
    </form>
  </div></div></div>
@endsection
