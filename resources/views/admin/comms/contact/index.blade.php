@extends('layouts.admin')
@section('title', 'Contact enquiries')
@section('content')
  @include('admin.partials.page-header', ['title' => 'Contact enquiries', 'crumbs' => ['Comms', 'Enquiries']])

  @if ($messages->isEmpty())
    <div class="card"><div class="card-body text-muted text-center py-5">No enquiries yet. Messages sent from the public contact form appear here.</div></div>
  @else
    <div class="mb-2 text-muted small">{{ $unread }} unread of {{ $messages->count() }}</div>
    <div class="vstack gap-2">
      @foreach ($messages as $m)
        <div class="card {{ $m->is_read ? '' : 'border-primary' }}"><div class="card-body">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
              <span class="fw-semibold">{{ $m->name }}</span>
              @unless ($m->is_read)<span class="badge text-bg-primary">New</span>@endunless
              <div class="small text-muted">
                @if ($m->email)<a href="mailto:{{ $m->email }}" class="text-decoration-none">{{ $m->email }}</a>@endif
                @if ($m->phone) · <a href="tel:{{ $m->phone }}" class="text-decoration-none">{{ $m->phone }}</a>@endif
                · {{ $m->created_at->format('d M Y, H:i') }}
              </div>
              @if ($m->subject)<div class="fw-semibold mt-2">{{ $m->subject }}</div>@endif
            </div>
            <div class="btn-group btn-group-sm">
              <form method="POST" action="{{ route('admin.enquiries.read', $m->id) }}">@csrf @method('PATCH')
                <button class="btn btn-outline-secondary">{{ $m->is_read ? 'Mark unread' : 'Mark read' }}</button></form>
              <form method="POST" action="{{ route('admin.enquiries.destroy', $m->id) }}" onsubmit="return confirm('Delete this message?')">@csrf @method('DELETE')
                <button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>
            </div>
          </div>
          <p class="mb-0 mt-2" style="white-space:pre-wrap;">{{ $m->message }}</p>
        </div></div>
      @endforeach
    </div>
  @endif
@endsection
