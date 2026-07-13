@extends('platform.layout')
@section('title', $school->name)
@section('content')
  <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item">Platform</li><li class="breadcrumb-item"><a href="{{ route('platform.schools.index') }}" class="text-decoration-none">Schools</a></li><li class="breadcrumb-item active">{{ $school->name }}</li></ol></nav>
  <h1 class="h4 mb-3">{{ $school->name }} @if ($school->is_demo)<span class="badge text-bg-warning align-middle">Demo</span>@endif</h1>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card"><div class="card-header">Overview</div><div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4 text-muted fw-normal">Subdomain</dt><dd class="col-sm-8">{{ $school->subdomain ? $school->subdomain.'.yourapp.com' : '—' }}</dd>
          <dt class="col-sm-4 text-muted fw-normal">Country</dt><dd class="col-sm-8">{{ $school->country_code ?? '—' }}</dd>
          <dt class="col-sm-4 text-muted fw-normal">Current plan</dt><dd class="col-sm-8">{{ $school->plan?->name ?? '— (uncapped legacy)' }}</dd>
          <dt class="col-sm-4 text-muted fw-normal">Provisioning</dt><dd class="col-sm-8 text-capitalize">{{ str_replace('_',' ', $school->provisioning_type ?? '—') }}</dd>
          <dt class="col-sm-4 text-muted fw-normal">Subscription status</dt><dd class="col-sm-8 text-capitalize">{{ str_replace('_',' ', $school->subscription_status ?? 'n/a') }}</dd>
          <dt class="col-sm-4 text-muted fw-normal">Trial ends</dt><dd class="col-sm-8">{{ $school->trial_ends_at?->format('d M Y') ?? '—' }}</dd>
          <dt class="col-sm-4 text-muted fw-normal">Subscription expires</dt>
          <dd class="col-sm-8">{{ $school->subscription_expires_at?->format('d M Y') ?? '—' }}
            @if ($school->subscription_expires_at?->isPast())<span class="badge text-bg-danger">Overdue</span>@endif</dd>
          <dt class="col-sm-4 text-muted fw-normal">Stripe customer</dt><dd class="col-sm-8">{{ $school->stripe_customer_id ? 'linked' : '—' }}</dd>
        </dl>
      </div></div>

      <div class="card mt-3"><div class="card-header">Renewal reminders sent</div><div class="card-body">
        @if ($reminders->isEmpty())
          <p class="text-muted mb-0">None sent yet.</p>
        @else
          <table class="table table-sm align-middle mb-0">
            <thead><tr><th>Milestone</th><th>Sent at</th></tr></thead>
            <tbody>@foreach ($reminders as $r)
              <tr><td>{{ str_replace('_',' ', $r->milestone) }}</td><td>{{ $r->sent_at?->format('d M Y H:i') ?? '—' }}</td></tr>
            @endforeach</tbody>
          </table>
        @endif
      </div></div>
    </div>

    <div class="col-lg-5">
      <div class="card"><div class="card-header">Change plan / extend</div><div class="card-body">
        @if ($school->is_demo)
          <p class="text-muted mb-0">The shared Demo school’s plan cannot be changed.</p>
        @else
          <form method="POST" action="{{ route('platform.schools.plan', $school->id) }}">
            @csrf @method('PATCH')
            <div class="mb-3"><label class="form-label">Plan</label>
              <select name="plan_id" class="form-select" required>
                @foreach ($plans as $p)
                  <option value="{{ $p->id }}" @selected($school->plan_id==$p->id)>{{ $p->name }}</option>
                @endforeach
              </select></div>
            <div class="mb-3"><label class="form-label">New expiry <span class="text-muted small">(optional)</span></label>
              <input type="date" name="subscription_expires_at" class="form-control"
                value="{{ optional($school->subscription_expires_at)->format('Y-m-d') }}">
              <div class="form-text">Leave as-is to keep the current expiry.</div></div>
            <button class="btn btn-primary w-100">Save</button>
          </form>
        @endif
      </div></div>
    </div>
  </div>
@endsection
