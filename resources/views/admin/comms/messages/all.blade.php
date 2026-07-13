@extends('layouts.admin')
@section('title', 'All conversations')
@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item">Comms</li><li class="breadcrumb-item"><a href="{{ route('admin.messages.index') }}" class="text-decoration-none">Messages</a></li><li class="breadcrumb-item active">All</li></ol></nav>
      <h1 class="h4 mb-0">All conversations <span class="badge text-bg-secondary align-middle">Oversight</span></h1>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('admin.messages.index') }}"><i class="bi bi-inbox"></i> My inbox</a>
  </div>
  <p class="text-muted small mb-3">Read-only visibility across the school. You can open any conversation and lock it, but you are not added as a participant.</p>

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Conversation</th><th>Type</th><th>Participants</th><th>Last activity</th><th>Status</th><th class="text-end" data-orderable="false"></th></tr></thead>
      <tbody>
        @foreach ($threads as $t)
          @php
            $names = $t->participants->pluck('user_id')->map(fn ($id) => $userMap[$id] ?? 'User #'.$id)->filter();
            $title = $t->subject ?: $names->join(', ') ?: 'Conversation';
          @endphp
          <tr>
            <td class="fw-semibold">{{ $title }}</td>
            <td><span class="badge text-bg-light border text-muted">{{ ucfirst($t->type) }}</span></td>
            <td class="small text-muted">{{ $names->count() }}</td>
            <td data-order="{{ optional($t->last_message_at)->timestamp ?? 0 }}">{{ $t->last_message_at?->diffForHumans() ?? '—' }}</td>
            <td>@if ($t->is_locked)<span class="badge text-bg-danger">Locked</span>@else<span class="badge text-bg-success">Open</span>@endif</td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="{{ route('admin.messages.show', $t->id) }}">Open</a>
              <form method="POST" action="{{ route('admin.messages.lock', $t->id) }}" class="d-inline">
                @csrf @method('PATCH')
                <button class="btn btn-sm btn-outline-{{ $t->is_locked ? 'success' : 'warning' }}">{{ $t->is_locked ? 'Unlock' : 'Lock' }}</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>
@endsection
