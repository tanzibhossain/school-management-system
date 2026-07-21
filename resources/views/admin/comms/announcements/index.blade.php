@extends('layouts.admin')
@section('title', __('Announcements'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Announcements',
    'crumbs' => ['Comms', 'Announcements'],
    'action' => ['label' => 'New announcement', 'modal' => 'createModal'],
  ])

  @php
    $statusOf = function ($a) {
      if ($a->expire_at && $a->expire_at->isPast()) return ['Expired', 'secondary'];
      if (! $a->publish_at) return ['Draft', 'light'];
      if ($a->publish_at->isFuture()) return ['Scheduled', 'info'];
      return ['Published', 'success'];
    };
  @endphp

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Title') }}</th><th>{{ __('Type') }}</th><th>{{ __('Audience') }}</th><th>{{ __('Priority') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($items as $a)
          @php [$label, $clr] = $statusOf($a); @endphp
          <tr>
            <td class="fw-semibold">@if ($a->is_pinned)<i class="bi bi-pin-angle-fill text-warning" title="{{ __('Pinned') }}"></i> @endif{{ $a->title }}</td>
            <td class="text-capitalize">{{ $a->type }}</td>
            <td class="text-capitalize">{{ $a->audience }}</td>
            <td><span class="badge text-bg-{{ $a->priority === 'urgent' ? 'danger' : ($a->priority === 'important' ? 'warning' : 'light border text-muted') }}">{{ ucfirst($a->priority) }}</span></td>
            <td><span class="badge text-bg-{{ $clr }} {{ $clr === 'light' ? 'border text-muted' : '' }}">{{ $label }}</span></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $a->id }}">{{ __('Edit') }}</button>
              @if ($label === 'Draft' || $label === 'Scheduled')
                <form method="POST" action="{{ route('admin.announcements.publish', $a->id) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">{{ __('Publish') }}</button></form>
              @elseif ($label === 'Published')
                <form method="POST" action="{{ route('admin.announcements.expire', $a->id) }}" class="d-inline">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning">{{ __('Expire') }}</button></form>
              @endif
              <form method="POST" action="{{ route('admin.announcements.destroy', $a->id) }}" class="d-inline" onsubmit="return confirm('Delete this announcement?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button></form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @include('admin.comms.announcements._form', ['mode' => 'create'])
  @foreach ($items as $a)
    @include('admin.comms.announcements._form', ['mode' => 'edit', 'a' => $a])
  @endforeach
@endsection
