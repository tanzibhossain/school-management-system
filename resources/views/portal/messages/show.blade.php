@extends('layouts.portal')
@section('title', __('Conversation'))
@section('heading', 'Conversation')
@section('content')

  @php
    $others = $thread->participants->pluck('user_id')->reject(fn ($id) => $id === auth()->id());
    $names = $others->map(fn ($id) => $userMap[$id] ?? 'User')->join(', ');
    $title = $thread->subject ?: ($names ?: 'Conversation');
  @endphp

  <div class="mb-3">
    <a href="{{ route('portal.messages') }}" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>{{ __('Back to messages') }}</a>
    <h1 class="h5 mt-2 mb-0">{{ $title }}</h1>
    <div class="text-muted small">{{ $names }}</div>
  </div>

  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  <div class="card mb-3">
    <div class="card-body" style="max-height:60vh; overflow-y:auto;">
      @forelse($messages as $m)
        @php $mine = $m->sender_id === auth()->id(); @endphp
        <div class="d-flex mb-3 {{ $mine ? 'justify-content-end' : '' }}">
          <div style="max-width:75%;">
            <div class="small text-muted mb-1 {{ $mine ? 'text-end' : '' }}">
              {{ $mine ? 'You' : ($userMap[$m->sender_id] ?? 'User') }} · {{ optional($m->created_at)->format('j M, g:i a') }}
            </div>
            <div class="p-2 px-3 rounded-3" style="background:{{ $mine ? '#eef2ff' : '#f1f5f9' }};">
              {!! nl2br(e($m->body)) !!}
              @foreach($m->attachments as $att)
                <div class="small mt-1"><i class="bi bi-paperclip"></i> {{ $att->original_name ?? 'attachment' }}</div>
              @endforeach
            </div>
          </div>
        </div>
      @empty
        <div class="text-muted text-center py-4">{{ __('No messages yet.') }}</div>
      @endforelse
    </div>
  </div>

  @if($thread->is_locked)
    <div class="alert alert-secondary"><i class="bi bi-lock me-1"></i> {{ __('This conversation has been locked by an administrator.') }}</div>
  @else
    <form method="POST" action="{{ route('portal.messages.reply', $thread->id) }}">
      @csrf
      <div class="input-group">
        <textarea name="body" class="form-control" rows="2" placeholder="Write a reply…" required></textarea>
        <button class="btn btn-primary"><i class="bi bi-send"></i></button>
      </div>
    </form>
  @endif

@endsection
