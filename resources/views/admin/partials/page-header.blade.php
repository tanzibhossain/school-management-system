{{--
  Reusable page header: breadcrumb + title + optional action button.
  Usage:
    @include('admin.partials.page-header', [
      'title' => 'Academic years',
      'crumbs' => ['Setup', 'Academic years'],
      'action' => ['label' => 'New year', 'modal' => 'createModal'],  // or 'url' => '...'
    ])
--}}
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
  <div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb small mb-1">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">{{ __('Home') }}</a></li>
        @foreach ($crumbs ?? [] as $c)
          <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">{{ $c }}</li>
        @endforeach
      </ol>
    </nav>
    <h1 class="h4 mb-0 page-title">{{ $title }}</h1>
  </div>
  @isset($action)
    @if (!empty($action['modal']))
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#{{ $action['modal'] }}">
        <i class="bi bi-plus-lg"></i> {{ $action['label'] }}
      </button>
    @elseif (!empty($action['url']))
      <a class="btn btn-primary" href="{{ $action['url'] }}"><i class="bi bi-plus-lg"></i> {{ $action['label'] }}</a>
    @endif
  @endisset
</div>
