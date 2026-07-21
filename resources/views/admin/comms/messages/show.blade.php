@extends('layouts.admin')
@section('title', __('Conversation'))
@section('content')
  @php
    $names = $thread->participants->pluck('user_id')->map(fn ($id) => $userMap[$id] ?? 'User #'.$id)->filter();
    $title = $thread->subject ?: $names->join(', ') ?: 'Conversation';
  @endphp
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item">{{ __('Comms') }}</li><li class="breadcrumb-item"><a href="{{ route('admin.messages.index') }}" class="text-decoration-none">{{ __('Messages') }}</a></li><li class="breadcrumb-item active">{{ $title }}</li></ol></nav>
      <h1 class="h5 mb-0">{{ $title }}
        <span class="badge text-bg-light border text-muted align-middle">{{ ucfirst($thread->type) }}</span>
        @if ($thread->is_locked)<span class="badge text-bg-danger align-middle">{{ __('Locked') }}</span>@endif
      </h1>
    </div>
    <form method="POST" action="{{ route('admin.messages.lock', $thread->id) }}">
      @csrf @method('PATCH')
      <button class="btn btn-sm btn-outline-{{ $thread->is_locked ? 'success' : 'warning' }}">
        <i class="bi bi-{{ $thread->is_locked ? 'unlock' : 'lock' }}"></i> {{ $thread->is_locked ? 'Unlock' : 'Lock' }}
      </button>
    </form>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card"><div class="card-body" style="max-height:60vh; overflow-y:auto;">
        @forelse ($messages as $m)
          @php $mine = $m->sender_id === auth()->id(); @endphp
          <div class="d-flex mb-3 {{ $mine ? 'justify-content-end' : '' }}">
            <div class="p-2 px-3 rounded-3 {{ $mine ? 'bg-primary text-white' : 'bg-light' }}" style="max-width:80%;">
              <div class="small fw-semibold mb-1 {{ $mine ? 'text-white-50' : 'text-muted' }}">{{ $userMap[$m->sender_id] ?? 'User #'.$m->sender_id }}</div>
              <div style="white-space:pre-wrap;">{{ $m->body }}</div>
              @foreach ($m->attachments as $a)
                <div class="small mt-1"><i class="bi bi-paperclip"></i> {{ $a->original_name }}</div>
              @endforeach
              <div class="small mt-1 {{ $mine ? 'text-white-50' : 'text-muted' }}">{{ $m->created_at->diffForHumans() }}</div>
            </div>
          </div>
        @empty
          <p class="text-muted text-center py-4 mb-0">{{ __('No messages yet.') }}</p>
        @endforelse
      </div></div>

      @if ($isParticipant)
        @if ($thread->is_locked)
          <div class="alert alert-secondary mt-3 mb-0"><i class="bi bi-lock"></i> {{ __('This conversation is locked — no new replies can be posted.') }}</div>
        @else
          <form method="POST" action="{{ route('admin.messages.reply', $thread->id) }}" class="card mt-3"><div class="card-body">
            @csrf
            <div class="input-group">
              <textarea name="body" class="form-control" rows="2" placeholder="Write a reply…" required></textarea>
              <button class="btn btn-primary"><i class="bi bi-send"></i></button>
            </div>
          </div></form>
        @endif
      @else
        <div class="alert alert-info mt-3 mb-0"><i class="bi bi-eye"></i> {{ __('You are viewing this as an admin (oversight). You are not a participant, so you cannot reply.') }}</div>
      @endif
    </div>

    <div class="col-lg-4">
      <div class="card"><div class="card-header">{{ __('Participants') }}</div><div class="list-group list-group-flush">
        @foreach ($thread->participants as $p)
          <div class="list-group-item d-flex justify-content-between align-items-center {{ $p->left_at ? 'text-muted' : '' }}">
            <span>{{ $userMap[$p->user_id] ?? 'User #'.$p->user_id }}</span>
            @if ($p->left_at)<span class="badge text-bg-light border text-muted">{{ __('Left') }}</span>@endif
          </div>
        @endforeach
      </div></div>
    </div>
  </div>
@endsection
