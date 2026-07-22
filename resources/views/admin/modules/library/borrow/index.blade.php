@extends('layouts.admin')
@section('title', __('Library — Borrow / Return'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => __('Borrow / return'),
    'crumbs' => [__('Library'), __('Borrow / return')],
    'action' => ['label' => __('Issue book'), 'modal' => 'borrowModal'],
  ])

  @include('admin.modules.library._tabs', ['active' => 'borrow'])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Book') }}</th><th>{{ __('Member') }}</th><th>{{ __('Borrowed') }}</th><th>{{ __('Due') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($records as $r)
          @php
            $outstanding = $r->returned_at === null;
            $overdue = $outstanding && $r->due_at !== null && $r->due_at->isPast();
          @endphp
          <tr>
            <td class="fw-semibold">{{ $r->book?->title ?? '—' }}</td>
            <td><code>{{ $r->member?->membership_number ?? '—' }}</code></td>
            <td class="small">{{ optional($r->borrowed_at)->format('d M Y') }}</td>
            <td class="small">{{ optional($r->due_at)->format('d M Y') }}</td>
            <td>
              @if (! $outstanding)
                <span class="badge text-bg-secondary">{{ __('Returned') }}</span>
              @elseif ($overdue)
                <span class="badge text-bg-danger">{{ __('Overdue') }}</span>
              @else
                <span class="badge text-bg-success">{{ __('Borrowed') }}</span>
              @endif
            </td>
            <td class="text-end">
              @if ($outstanding)
                <form method="POST" action="{{ route('admin.library.borrow.return', $r->id) }}" class="d-inline">
                  @csrf @method('PATCH')
                  <button class="btn btn-sm btn-outline-primary">{{ __('Mark Returned') }}</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="borrowModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.library.borrow.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('Issue Book') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">{{ __('Book') }} <span class="text-danger">*</span></label>
          <select name="book_id" class="form-select js-select" required>
            <option value="">— select —</option>
            @foreach ($books as $bk)<option value="{{ $bk->id }}">{{ $bk->title }} ({{ $bk->available_copies }} available)</option>@endforeach
          </select>
          @if ($books->isEmpty())<div class="form-text text-danger">{{ __('No Books With Available Copies.') }}</div>@endif
        </div>
        <div class="col-12"><label class="form-label">{{ __('Member') }} <span class="text-danger">*</span></label>
          <select name="library_member_id" class="form-select js-select" required>
            <option value="">— select —</option>
            @foreach ($members as $mem)<option value="{{ $mem->id }}">{{ $mem->membership_number }} ({{ $mem->member_type }})</option>@endforeach
          </select></div>
        <div class="col-md-6"><label class="form-label">{{ __('Due Date') }} <span class="text-danger">*</span></label>
          <input type="date" name="due_at" class="form-control" value="{{ now()->addWeeks(2)->format('Y-m-d') }}" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Issue') }}</button></div>
    </form>
  </div></div></div>
@endsection
