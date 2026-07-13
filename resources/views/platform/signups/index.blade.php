@extends('platform.layout')
@section('title', 'Pending signups')
@section('content')
  <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item">Platform</li><li class="breadcrumb-item active">Pending signups</li></ol></nav>
  <h1 class="h4 mb-1">Pending signups</h1>
  <p class="text-muted small mb-3">Staging rows for the paid self-serve flow (the Stripe Checkout round-trip). Provisioning completes automatically on webhook.</p>

  @php $sc = ['pending'=>'warning','completed'=>'success','failed'=>'danger','expired'=>'secondary']; @endphp
  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>School</th><th>Subdomain</th><th>Plan</th><th>Admin email</th><th>Status</th><th>Created school</th><th>Requested</th></tr></thead>
      <tbody>
        @forelse ($signups as $sg)
          <tr>
            <td class="fw-semibold">{{ $sg->school_name }}</td>
            <td><code>{{ $sg->desired_subdomain }}</code></td>
            <td>{{ $sg->plan?->name ?? '—' }}</td>
            <td>{{ $sg->admin_email }}</td>
            <td><span class="badge text-bg-{{ $sc[$sg->status] ?? 'secondary' }}">{{ ucfirst($sg->status) }}</span></td>
            <td>@if ($sg->createdSchool)<a href="{{ route('platform.schools.show', $sg->createdSchool->id) }}">{{ $sg->createdSchool->name }}</a>@else <span class="text-muted">—</span>@endif</td>
            <td>{{ $sg->created_at?->format('d M Y H:i') ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-muted text-center py-4">No signups yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div></div>
@endsection
