@extends('layouts.staff')
@section('title', __('Dashboard'))
@section('heading', 'Dashboard')
@section('content')

  <div class="mb-4">
    <h1 class="h4 mb-1">Welcome{{ $staff ? ', ' . e($staff->name) : '' }}</h1>
    <p class="text-muted small mb-0">Here's your teaching overview for today, {{ now()->format('l, j M Y') }}.</p>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
      <div class="card h-100"><div class="card-body d-flex align-items-center justify-content-between">
        <div>
          <div class="text-muted small mb-1">{{ __('My subject') }}</div>
          <div class="h5 mb-0">{{ $staff?->subject?->name ?? '—' }}</div>
        </div>
        <span class="avatar-sm" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-book"></i></span>
      </div></div>
    </div>
    <div class="col-sm-6 col-lg-4">
      <div class="card h-100"><div class="card-body d-flex align-items-center justify-content-between">
        <div>
          <div class="text-muted small mb-1">{{ __('Classes I lead') }}</div>
          <div class="h5 mb-0">{{ $sections->count() }}</div>
        </div>
        <span class="avatar-sm" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-easel2"></i></span>
      </div></div>
    </div>
    <div class="col-sm-6 col-lg-4">
      <div class="card h-100"><div class="card-body d-flex align-items-center justify-content-between">
        <div>
          <div class="text-muted small mb-1">{{ __('My students') }}</div>
          <div class="h5 mb-0">{{ $studentCount }}</div>
        </div>
        <span class="avatar-sm" style="width:44px;height:44px;font-size:1.2rem;"><i class="bi bi-people"></i></span>
      </div></div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card h-100">
        <div class="card-header">My classes &amp; sections</div>
        <div class="card-body p-0">
          @if($sections->isEmpty())
            <div class="text-center text-muted py-5"><i class="bi bi-easel2 fs-3 d-block mb-2 opacity-50"></i>{{ __('You are not assigned as a class teacher yet.') }}</div>
          @else
            <table class="table align-middle mb-0">
              <thead class="table-light"><tr><th>{{ __('Class') }}</th><th>{{ __('Section') }}</th><th>{{ __('Shift') }}</th></tr></thead>
              <tbody>
                @foreach($sections as $sec)
                  <tr>
                    <td class="fw-medium">{{ $sec->schoolClass->name ?? '—' }}</td>
                    <td>{{ $sec->name }}</td>
                    <td><span class="badge text-bg-light">{{ $sec->shift->name ?? '—' }}</span></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          @endif
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>{{ __('Notices') }}</span>
          <a href="{{ route('staff.notices') }}" class="small text-decoration-none">{{ __('View all') }}</a>
        </div>
        <div class="card-body">
          @forelse($notices as $n)
            <div class="{{ !$loop->last ? 'border-bottom pb-2 mb-2' : '' }}">
              <div class="fw-medium small">@if($n->is_pinned)<i class="bi bi-pin-angle-fill text-primary me-1"></i>@endif{{ $n->title }}</div>
              <div class="text-muted" style="font-size:.78rem;">{{ optional($n->publish_at)->format('j M Y') }}</div>
            </div>
          @empty
            <div class="text-muted small text-center py-3">{{ __('No notices published.') }}</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>

@endsection
