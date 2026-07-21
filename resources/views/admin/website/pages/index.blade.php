@extends('layouts.admin')
@section('title', __('Website pages'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Website pages',
    'crumbs' => ['Website', 'Pages'],
    'action' => ['label' => 'New page', 'url' => route('admin.pages.create')],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Title') }}</th><th>{{ __('Slug') }}</th><th>{{ __('Status') }}</th><th>{{ __('Homepage') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($pages as $p)
          <tr>
            <td class="fw-semibold">{{ $p->title }}</td>
            <td><code>/{{ $p->slug }}</code></td>
            <td>
              @if ($p->status === 'published')<span class="badge text-bg-success">{{ __('Published') }}</span>
              @else<span class="badge text-bg-secondary">{{ __('Draft') }}</span>@endif
            </td>
            <td>@if ($p->is_homepage)<span class="badge text-bg-primary"><i class="bi bi-house"></i> {{ __('Homepage') }}</span>@endif</td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.pages.edit', $p->id) }}">{{ __('Edit') }}</a>
              @if ($p->status === 'published')<a class="btn btn-sm btn-outline-secondary" href="{{ url('/' . $p->slug) }}" target="_blank">{{ __('View') }}</a>@endif
              @unless ($p->is_homepage)
                <form method="POST" action="{{ route('admin.pages.homepage', $p->id) }}" class="d-inline">
                  @csrf<button class="btn btn-sm btn-outline-secondary" title="{{ __('Set as homepage') }}"><i class="bi bi-house"></i></button>
                </form>
              @endunless
              <form method="POST" action="{{ route('admin.pages.destroy', $p->id) }}" class="d-inline" onsubmit="return confirm('Delete “{{ $p->title }}”?')">
                @csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>
@endsection
