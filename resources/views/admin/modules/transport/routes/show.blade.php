@extends('layouts.admin')
@section('title', 'Route — ' . $route->name)
@section('content')
  @include('admin.partials.page-header', [
    'title'  => $route->name,
    'crumbs' => [__('Transport'), __('Routes'), $route->name],
  ])
  <div class="mb-3"><a href="{{ route('admin.transport.routes.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> {{ __('Back To Routes') }}</a></div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card mb-4"><div class="card-header">{{ __('Vehicle') }}</div><div class="card-body">
        <p class="mb-2">Current: <strong>{{ $route->vehicle?->registration_no ?? 'none assigned' }}</strong>
          @if ($route->vehicle)<span class="text-muted small">(seats {{ $route->vehicle->capacity }})</span>@endif
        </p>
        <form method="POST" action="{{ route('admin.transport.routes.set-vehicle', $route->id) }}" class="row g-2">
          @csrf @method('PATCH')
          <div class="col-8"><select name="vehicle_id" class="form-select form-select-sm">
            <option value="">— detach vehicle —</option>
            @if ($route->vehicle)<option value="{{ $route->vehicle->id }}" selected>{{ $route->vehicle->registration_no }} (current)</option>@endif
            @foreach ($vehicles as $v)<option value="{{ $v->id }}">{{ $v->registration_no }} (seats {{ $v->capacity }})</option>@endforeach
          </select></div>
          <div class="col-4"><button class="btn btn-sm btn-primary w-100">{{ __('Set') }}</button></div>
        </form>
      </div></div>

      <div class="card"><div class="card-header">{{ __('Details') }}</div><div class="card-body">
        <dl class="row mb-0">
          <dt class="col-5 text-muted">{{ __('Fare') }}</dt><dd class="col-7">{{ number_format((float) $route->fare, 2) }}</dd>
          <dt class="col-5 text-muted">{{ __('Driver') }}</dt><dd class="col-7">{{ $route->driver?->name ?? '—' }}</dd>
          @if ($route->description)<dt class="col-5 text-muted">{{ __('Description') }}</dt><dd class="col-7">{{ $route->description }}</dd>@endif
        </dl>
      </div></div>
    </div>

    <div class="col-lg-7">
      <div class="card"><div class="card-header d-flex justify-content-between align-items-center">
        <span>Riders ({{ $riders->count() }})</span>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal" {{ $route->current_vehicle_id ? '' : 'disabled' }}><i class="bi bi-plus-lg"></i> {{ __('Add Rider') }}</button>
      </div><div class="card-body">
        @unless ($route->current_vehicle_id)
          <div class="alert alert-warning py-2 small">{{ __('Assign An Operational Vehicle Before Adding Riders.') }}</div>
        @endunless
        @if ($riders->isEmpty())
          <p class="text-muted mb-0">{{ __('No Active Riders.') }}</p>
        @else
          <table class="table align-middle mb-0">
            <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Pickup') }}</th><th>{{ __('Since') }}</th><th class="text-end" data-orderable="false"></th></tr></thead>
            <tbody>
              @foreach ($riders as $a)
                <tr>
                  <td class="fw-semibold">{{ $a->student?->name ?? '—' }} <span class="text-muted small">({{ $a->student?->student_id }})</span></td>
                  <td>{{ $a->pickup_point ?? '—' }}</td>
                  <td class="small">{{ optional($a->starts_on)->format('d M Y') }}</td>
                  <td class="text-end">
                    <form method="POST" action="{{ route('admin.transport.routes.riders.end', [$route->id, $a->id]) }}" onsubmit="return confirm('Remove this rider?')">
                      @csrf @method('PATCH')
                      <button class="btn btn-sm btn-outline-danger">{{ __('Remove') }}</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div></div>
    </div>
  </div>

  <div class="modal fade" id="assignModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.transport.routes.riders.assign', $route->id) }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('Add Rider') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">{{ __('Student') }} <span class="text-danger">*</span></label>
          <select name="student_id" class="form-select js-select" required>
            <option value="">— select —</option>
            @foreach ($students as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ $s->student_id }})</option>@endforeach
          </select></div>
        <div class="col-md-7"><label class="form-label">{{ __('Pickup Point') }}</label>
          <input name="pickup_point" class="form-control"></div>
        <div class="col-md-5"><label class="form-label">{{ __('Starts On') }}</label>
          <input type="date" name="starts_on" class="form-control" value="{{ now()->format('Y-m-d') }}"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Assign') }}</button></div>
    </form>
  </div></div></div>
@endsection
