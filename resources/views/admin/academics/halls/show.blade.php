@extends('layouts.admin')
@section('title', $hall->name . ' — seats')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => $hall->name . ' — seat map',
    'crumbs' => ['Academics', 'Exam halls', $hall->name],
  ])

  <div class="mb-3"><a href="{{ route('admin.exam-halls.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> {{ __('Back to halls') }}</a></div>

  @if ($assigned)
    <div class="alert alert-warning"><i class="bi bi-info-circle"></i> This hall has active seating assignments — seats can't be toggled or regenerated until those are cleared.</div>
  @endif

  <div class="card"><div class="card-body">
    <div class="d-flex gap-3 mb-3 small text-muted">
      <span><span class="badge text-bg-success">&nbsp;</span> {{ __('Available') }}</span>
      <span><span class="badge text-bg-secondary">&nbsp;</span> {{ __('Blocked') }}</span>
      <span class="ms-auto">{{ __('Click a seat to toggle its availability.') }}</span>
    </div>
    @foreach ($rows as $rowNo => $seats)
      <div class="d-flex align-items-center gap-1 mb-1 flex-wrap">
        <span class="text-muted small me-2" style="width:2.5rem">R{{ sprintf('%02d', $rowNo) }}</span>
        @foreach ($seats as $seat)
          <form method="POST" action="{{ route('admin.exam-halls.seats.toggle', [$hall->id, $seat->id]) }}" class="d-inline">
            @csrf @method('PATCH')
            <button class="btn btn-sm {{ $seat->is_available ? 'btn-success' : 'btn-secondary' }}" style="width:3.6rem" {{ $assigned ? 'disabled' : '' }} title="{{ $seat->label }}">{{ $seat->side }}{{ $seat->position }}</button>
          </form>
        @endforeach
      </div>
    @endforeach
  </div></div>
@endsection
