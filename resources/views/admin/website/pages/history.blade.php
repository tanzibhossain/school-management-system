@extends('layouts.admin')
@section('title', __('Page History'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => __('Page History') . ' — ' . $page->title,
    'crumbs' => [__('Website'), __('Pages'), $page->title, __('History')],
    'action' => ['label' => __('Back To Editor'), 'url' => route('admin.pages.edit', $page->id)],
  ])

  <div class="card"><div class="card-body">
    @if ($page->layouts->isEmpty())
      <p class="text-muted mb-0">{{ __('No Saved Revisions Yet.') }}</p>
    @else
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>{{ __('Saved') }}</th>
            <th>{{ __('By') }}</th>
            <th>{{ __('Status') }}</th>
            <th>{{ __('Blocks') }}</th>
            <th class="text-end">{{ __('Actions') }}</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($page->layouts as $i => $revision)
            <tr>
              <td>{{ $revision->created_at?->format('d M Y, H:i') }}</td>
              <td>{{ $revision->createdBy?->name ?? __('—') }}</td>
              <td>
                @if ($i === 0)<span class="badge text-bg-primary">{{ __('Latest') }}</span>@endif
                @if ($revision->is_published)<span class="badge text-bg-success">{{ __('Published') }}</span>@endif
              </td>
              <td class="text-muted small">
                {{ __(':count Main + :sidebar Sidebar', ['count' => count($revision->layout_json['blocks'] ?? []), 'sidebar' => count($revision->layout_json['sidebar'] ?? [])]) }}
              </td>
              <td class="text-end">
                @if ($i === 0)
                  <span class="text-muted small">{{ __('Current') }}</span>
                @else
                  <form method="POST" action="{{ route('admin.pages.restore', [$page->id, $revision->id]) }}" class="d-inline" onsubmit="return confirm({{ json_encode(__('Restore this revision as a new draft? Your current unsaved edits in the editor (if any) will not be affected — this only adds a new saved version.')) }})">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i> {{ __('Restore') }}</button>
                  </form>
                @endif
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div></div>
@endsection
