@extends('layouts.portal')
@section('title', __('Leave'))
@section('heading', 'Leave')
@section('content')

  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
  @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif

  <div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted small mb-0">Apply for {{ $isGuardian ? "your child's" : 'your' }} leave and track requests. Approvals are handled by the school.</p>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#leaveModal" @disabled($leaveTypes->isEmpty())><i class="bi bi-plus-lg me-1"></i> {{ __('Apply For Leave') }}</button>
  </div>

  <div class="card">
    <div class="card-body p-0">
      <table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>{{ __('Type') }}</th><th>{{ __('From') }}</th><th>To</th><th class="text-center">{{ __('Days') }}</th><th class="text-center">{{ __('Status') }}</th><th class="text-end">{{ __('Action') }}</th></tr></thead>
        <tbody>
          @forelse($requests as $r)
            @php
              $badge = match($r->status) {
                'approved' => 'text-bg-success', 'rejected' => 'text-bg-danger',
                'cancelled' => 'text-bg-secondary', default => 'text-bg-warning',
              };
            @endphp
            <tr>
              <td class="fw-medium">{{ $r->leaveType->name ?? '—' }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($r->from_date)->format('j M Y') }}</td>
              <td>{{ \Illuminate\Support\Carbon::parse($r->to_date)->format('j M Y') }}</td>
              <td class="text-center">{{ $r->working_days }}</td>
              <td class="text-center"><span class="badge {{ $badge }}">{{ ucfirst($r->status) }}</span>
                @if($r->status === 'rejected' && $r->rejection_reason)<div class="text-muted small mt-1">{{ $r->rejection_reason }}</div>@endif
              </td>
              <td class="text-end">
                @if($r->status === 'pending')
                  <form method="POST" action="{{ route('portal.leave.cancel', $r->id) }}" onsubmit="return confirm('Withdraw this leave request?')">
                    @csrf @method('PATCH')
                    <button class="btn btn-sm btn-outline-danger">{{ __('Withdraw') }}</button>
                  </form>
                @else — @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted py-4">{{ __('No Leave Requests Yet.') }}</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- Apply modal --}}
  <div class="modal fade" id="leaveModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('portal.leave.store', ['student' => $student->id]) }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('Apply For Leave') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">{{ __('Leave Type') }}</label>
          <select name="leave_type_id" class="form-select" required>
            <option value="">— Select —</option>
            @foreach($leaveTypes as $lt)<option value="{{ $lt->id }}">{{ $lt->name }}</option>@endforeach
          </select>
        </div>
        <div class="row g-2 mb-3">
          <div class="col"><label class="form-label">{{ __('From') }}</label><input type="date" name="from_date" class="form-control" required></div>
          <div class="col"><label class="form-label">To</label><input type="date" name="to_date" class="form-control" required></div>
        </div>
        <div class="mb-0"><label class="form-label">{{ __('Reason') }}</label><textarea name="reason" rows="3" class="form-control" required></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
        <button class="btn btn-primary">{{ __('Submit Request') }}</button>
      </div>
    </form>
  </div></div></div>

@endsection
