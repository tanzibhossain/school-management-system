@extends('layouts.staff')
@section('title', __('Messages'))
@section('heading', 'Messages')
@section('content')

  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted small mb-0">{{ __('Conversations with families and colleagues.') }}</p>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#composeModal"><i class="bi bi-pencil-square me-1"></i> {{ __('New message') }}</button>
  </div>

  <div class="card">
    <div class="card-body p-0">
      @forelse($threads as $thread)
        @php
          $others = $thread->participants->pluck('user_id')->reject(fn ($id) => $id === auth()->id());
          $names = $others->map(fn ($id) => $userMap[$id] ?? 'User')->join(', ');
          $title = $thread->subject ?: ($names ?: 'Conversation');
        @endphp
        <a href="{{ route('staff.messages.show', $thread->id) }}" class="d-flex align-items-center gap-3 text-decoration-none text-body px-3 py-3 border-bottom">
          <span class="avatar-sm"><i class="bi bi-chat-left-text"></i></span>
          <div class="flex-grow-1 min-w-0">
            <div class="fw-medium text-truncate">{{ $title }}</div>
            <div class="text-muted small">{{ $names ?: '—' }}</div>
          </div>
          <div class="text-end">
            @if($thread->unread_count > 0)<span class="badge text-bg-primary rounded-pill">{{ $thread->unread_count }}</span>@endif
            <div class="text-muted" style="font-size:.72rem;">{{ optional($thread->last_message_at)->diffForHumans() }}</div>
          </div>
        </a>
      @empty
        <div class="text-center text-muted py-5"><i class="bi bi-chat-left-text fs-3 d-block mb-2 opacity-50"></i>{{ __('No conversations yet.') }}</div>
      @endforelse
    </div>
  </div>

  {{-- Compose --}}
  <div class="modal fade" id="composeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('staff.messages.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('New message') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">{{ __('Recipients') }}</label>
          <select name="participant_ids[]" class="form-select" multiple size="6" required>
            @foreach($users as $u)
              <option value="{{ $u['id'] }}">{{ $u['label'] }}</option>
            @endforeach
          </select>
          <div class="form-text">{{ __('Hold Ctrl/Cmd to select more than one.') }}</div>
        </div>
        <div class="mb-3"><label class="form-label">{{ __('Subject') }} <span class="text-muted small">(optional)</span></label>
          <input name="subject" class="form-control" value="{{ old('subject') }}"></div>
        <div class="mb-0"><label class="form-label">{{ __('Message') }}</label>
          <textarea name="body" rows="4" class="form-control" required>{{ old('body') }}</textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button class="btn btn-primary">{{ __('Send') }}</button>
      </div>
    </form>
  </div></div></div>

@endsection
