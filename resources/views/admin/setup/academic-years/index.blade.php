@extends('layouts.admin')
@section('title', __('Academic Years'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => __('Academic years'),
    'crumbs' => [__('Setup'), __('Academic years')],
    'action' => ['label' => __('New year'), 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Year') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($years as $y)
          <tr>
            <td class="fw-semibold">{{ $y->year }}</td>
            <td>
              @if ($y->is_current)
                <span class="badge text-bg-success">{{ __('Current') }}</span>
              @else
                <span class="badge text-bg-light border text-muted">—</span>
              @endif
            </td>
            <td class="text-end">
              @unless ($y->is_current)
                <form method="POST" action="{{ route('admin.academic-years.set-current', $y->id) }}" class="d-inline">
                  @csrf
                  <button class="btn btn-sm btn-outline-success">{{ __('Set Current') }}</button>
                </form>
              @endunless
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $y->id }}">{{ __('Edit') }}</button>
              @unless ($y->is_current)
                <form method="POST" action="{{ route('admin.academic-years.destroy', $y->id) }}" class="d-inline" onsubmit="return confirm('Delete academic year {{ $y->year }}?')">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
                </form>
              @endunless
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  {{-- Create --}}
  <div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.academic-years.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('New Academic Year') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">{{ __('Year') }} <span class="text-danger">*</span></label>
        <input name="year" class="form-control" value="{{ old('year') }}" placeholder="{{ __('E.g. 2026 Or 2026-2027') }}" required>
        <div class="form-text">Format follows your school's academic-year pattern.</div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
    </form>
  </div></div></div>

  {{-- Edit --}}
  @foreach ($years as $y)
    <div class="modal fade" id="editModal{{ $y->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.academic-years.update', $y->id) }}">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">{{ __('Edit Academic Year') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <label class="form-label">{{ __('Year') }} <span class="text-danger">*</span></label>
          <input name="year" class="form-control" value="{{ $y->year }}" required>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
      </form>
    </div></div></div>
  @endforeach
@endsection
