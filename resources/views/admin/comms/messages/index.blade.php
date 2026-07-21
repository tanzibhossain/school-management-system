@extends('layouts.admin')
@section('title', __('Messages'))
@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item">{{ __('Comms') }}</li><li class="breadcrumb-item active">{{ __('Messages') }}</li></ol></nav>
      <h1 class="h4 mb-0">{{ __('Messages') }}</h1>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('admin.messages.all') }}"><i class="bi bi-eye"></i> {{ __('All conversations') }}</a>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeModal"><i class="bi bi-pencil-square"></i> {{ __('Compose') }}</button>
    </div>
  </div>

  <div class="card"><div class="card-body">
    @if ($threads->isEmpty())
      <p class="text-muted text-center py-4 mb-0">{{ __('Your inbox is empty. Start a conversation with Compose.') }}</p>
    @else
      <table class="table table-hover align-middle w-100 js-dt">
        <thead><tr><th>{{ __('Conversation') }}</th><th>{{ __('Type') }}</th><th>{{ __('Last activity') }}</th><th class="text-end">{{ __('Unread') }}</th><th data-orderable="false"></th></tr></thead>
        <tbody>
          @foreach ($threads as $t)
            @php
              $others = $t->participants->pluck('user_id')->reject(fn ($id) => $id === auth()->id())
                  ->map(fn ($id) => $userMap[$id] ?? 'User #'.$id)->filter()->values();
              $title = $t->subject ?: $others->join(', ') ?: 'Conversation';
            @endphp
            <tr>
              <td>
                <a href="{{ route('admin.messages.show', $t->id) }}" class="fw-semibold text-decoration-none">{{ $title }}</a>
                @if ($t->is_locked)<i class="bi bi-lock-fill text-muted" title="{{ __('Locked') }}"></i>@endif
              </td>
              <td><span class="badge text-bg-light border text-muted">{{ ucfirst($t->type) }}</span></td>
              <td data-order="{{ optional($t->last_message_at)->timestamp ?? 0 }}">{{ $t->last_message_at?->diffForHumans() ?? '—' }}</td>
              <td class="text-end">@if (($t->unread_count ?? 0) > 0)<span class="badge text-bg-primary">{{ $t->unread_count }}</span>@else <span class="text-muted">0</span>@endif</td>
              <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.messages.show', $t->id) }}">{{ __('Open') }}</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div></div>

  <div class="modal fade" id="composeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.messages.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('New conversation') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">To <span class="text-danger">*</span></label>
          <select name="participant_ids[]" class="form-select js-select" multiple required>
            @foreach ($users as $u)
              <option value="{{ $u['id'] }}">{{ $u['label'] }}</option>
            @endforeach
          </select>
          <div class="form-text">{{ __('Pick one person for a direct chat, or several for a group.') }}</div></div>
        <div class="mb-3"><label class="form-label">{{ __('Subject') }} <span class="text-muted small">(groups only)</span></label>
          <input name="subject" class="form-control" value="{{ old('subject') }}"></div>
        <div class="mb-0"><label class="form-label">{{ __('Message') }} <span class="text-danger">*</span></label>
          <textarea name="body" class="form-control" rows="4" required>{{ old('body') }}</textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Send') }}</button></div>
    </form>
  </div></div></div>
@endsection
