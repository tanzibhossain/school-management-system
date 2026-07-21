@extends('layouts.portal')
@section('title', __('Class Routine'))
@section('heading', 'Class Routine')
@section('content')

  @php $days = ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday']; @endphp

  @if($routine->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
      <i class="bi bi-calendar3-week fs-3 d-block mb-2 opacity-50"></i>No class routine has been published yet.
    </div></div>
  @else
    <div class="row g-3">
      @foreach($days as $key => $label)
        <div class="col-md-6 col-lg-4">
          <div class="card h-100">
            <div class="card-header">{{ $label }}</div>
            <div class="card-body p-0">
              @php $entries = $routine[$key] ?? collect(); @endphp
              @if($entries->isEmpty())
                <div class="text-muted small text-center py-3">{{ __('No Classes') }}</div>
              @else
                <ul class="list-group list-group-flush">
                  @foreach($entries as $e)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span class="fw-medium">{{ $e->subject->name ?? '—' }}</span>
                      <span class="text-muted small">{{ $e->teacher->name ?? '' }}</span>
                    </li>
                  @endforeach
                </ul>
              @endif
            </div>
          </div>
        </div>
      @endforeach
    </div>
  @endif

@endsection
