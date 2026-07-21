@extends('layouts.staff')
@section('title', __('Notices'))
@section('heading', 'Notices')
@section('content')

  <div class="card">
    <div class="card-header">{{ __('School notices') }}</div>
    <div class="card-body">
      @forelse($notices as $n)
        <div class="{{ !$loop->last ? 'border-bottom pb-3 mb-3' : '' }}">
          <div class="d-flex align-items-center gap-2">
            @if($n->is_pinned)<i class="bi bi-pin-angle-fill text-primary"></i>@endif
            <h6 class="mb-0">{{ $n->title }}</h6>
            <span class="text-muted ms-auto small">{{ optional($n->publish_at)->format('j M Y') }}</span>
          </div>
          <p class="text-muted small mb-0 mt-1">{{ \Illuminate\Support\Str::limit(strip_tags($n->body), 240) }}</p>
        </div>
      @empty
        <div class="text-muted text-center py-4">{{ __('No notices published.') }}</div>
      @endforelse
    </div>
  </div>

  <div class="mt-3">{{ $notices->links() }}</div>

@endsection
