@extends('platform.layout')
@section('title', 'Schools')
@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item">Platform</li><li class="breadcrumb-item active">Schools</li></ol></nav>
      <h1 class="h4 mb-0">Schools</h1>
    </div>
    <a class="btn btn-primary" href="{{ route('platform.schools.create') }}"><i class="bi bi-plus-lg"></i> Provision school</a>
  </div>

  @php
    $statusColor = ['active'=>'success','trialing'=>'info','past_due'=>'warning','canceled'=>'secondary','expired'=>'danger'];
  @endphp
  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>School</th><th>Subdomain</th><th>Plan</th><th>Provisioning</th><th>Status</th><th>Expires</th><th class="text-end" data-orderable="false"></th></tr></thead>
      <tbody>
        @foreach ($schools as $s)
          <tr>
            <td class="fw-semibold">{{ $s->name }} @if ($s->is_demo)<span class="badge text-bg-warning">Demo</span>@endif</td>
            <td>@if ($s->subdomain)<code>{{ $s->subdomain }}</code>@else <span class="text-muted">—</span>@endif</td>
            <td>{{ $s->plan?->name ?? '—' }}</td>
            <td class="text-capitalize">{{ str_replace('_', ' ', $s->provisioning_type ?? '—') }}</td>
            <td><span class="badge text-bg-{{ $statusColor[$s->subscription_status] ?? 'secondary' }}">{{ ucfirst(str_replace('_',' ', $s->subscription_status ?? 'n/a')) }}</span></td>
            <td>
              @if ($s->subscription_expires_at)
                {{ $s->subscription_expires_at->format('d M Y') }}
                @if ($s->subscription_expires_at->isPast())<span class="badge text-bg-danger">Overdue</span>@endif
              @else <span class="text-muted">—</span>@endif
            </td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('platform.schools.show', $s->id) }}">Manage</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>
@endsection
