@extends('layouts.portal')
@section('title', __('Dashboard'))
@section('heading', 'Dashboard')
@section('content')

  <div class="mb-4">
    <h1 class="h4 mb-1">{{ $student->name }}</h1>
    <p class="text-muted small mb-0">
      @if($enrollment)
        {{ $enrollment->schoolClass->name ?? '' }} · Section {{ $enrollment->section->name ?? '' }}
        @if($enrollment->shift) · {{ $enrollment->shift->name }} @endif
        · Roll {{ $enrollment->roll_number ?? '—' }}
      @else
        Admission no. {{ $student->admission_number }}
      @endif
    </p>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-sm-4">
      <div class="card h-100"><div class="card-body">
        <div class="text-muted small mb-1">{{ __('Attendance') }}</div>
        <div class="h4 mb-0">{{ $attendance['percent'] !== null ? $attendance['percent'] . '%' : '—' }}</div>
        <div class="text-muted" style="font-size:.78rem;">{{ $attendance['present'] }} / {{ $attendance['total'] }} days present</div>
      </div></div>
    </div>
    <div class="col-sm-4">
      <div class="card h-100"><div class="card-body">
        <div class="text-muted small mb-1">{{ __('Outstanding Dues') }}</div>
        <div class="h4 mb-0">{{ number_format($dues) }}</div>
        <div class="text-muted" style="font-size:.78rem;"><a href="{{ route('portal.fees', ['student' => $student->id]) }}" class="text-decoration-none">{{ __('View Fees') }}</a></div>
      </div></div>
    </div>
    <div class="col-sm-4">
      <div class="card h-100"><div class="card-body">
        <div class="text-muted small mb-1">{{ __('Published Results') }}</div>
        <div class="h4 mb-0">{{ $resultsCount }}</div>
        <div class="text-muted" style="font-size:.78rem;"><a href="{{ route('portal.results', ['student' => $student->id]) }}" class="text-decoration-none">{{ __('View Results') }}</a></div>
      </div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span>{{ __('Recent Notices') }}</span>
      <a href="{{ route('portal.notices', ['student' => $student->id]) }}" class="small text-decoration-none">{{ __('View All') }}</a>
    </div>
    <div class="card-body">
      @forelse($notices as $n)
        <div class="{{ !$loop->last ? 'border-bottom pb-2 mb-2' : '' }}">
          <div class="fw-medium small">@if($n->is_pinned)<i class="bi bi-pin-angle-fill text-primary me-1"></i>@endif{{ $n->title }}</div>
          <div class="text-muted" style="font-size:.78rem;">{{ optional($n->publish_at)->format('j M Y') }}</div>
        </div>
      @empty
        <div class="text-muted small text-center py-3">{{ __('No Notices Published.') }}</div>
      @endforelse
    </div>
  </div>

@endsection
