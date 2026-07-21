@extends('layouts.staff')
@section('title', __('My Attendance'))
@section('heading', 'My Attendance')
@section('content')

  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  @if(! $staff)
    <div class="alert alert-info">{{ __('No Staff Record Is Linked To Your Account Yet. Please Contact The Administrator.') }}</div>
  @else
    @php
      $checkedIn  = $todayRecord && $todayRecord->check_in;
      $checkedOut = $todayRecord && $todayRecord->check_out;
      $label = ! $checkedIn ? 'Clock in' : ($checkedOut ? 'Punch again' : 'Clock out');
    @endphp

    <div class="row g-3 mb-4">
      <div class="col-md-7">
        <div class="card h-100"><div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <div class="text-muted small mb-1">{{ \Illuminate\Support\Carbon::parse($today)->format('l, j M Y') }}</div>
            <div class="d-flex gap-4">
              <div>
                <div class="text-muted" style="font-size:.72rem;">{{ __('Clock In') }}</div>
                <div class="h5 mb-0">{{ $checkedIn ? \Illuminate\Support\Carbon::parse($todayRecord->check_in)->format('g:i a') : '—' }}</div>
              </div>
              <div>
                <div class="text-muted" style="font-size:.72rem;">{{ __('Clock Out') }}</div>
                <div class="h5 mb-0">{{ $checkedOut ? \Illuminate\Support\Carbon::parse($todayRecord->check_out)->format('g:i a') : '—' }}</div>
              </div>
            </div>
          </div>
          <form method="POST" action="{{ route('staff.clock.punch') }}">
            @csrf
            <button class="btn btn-primary btn-lg"><i class="bi bi-fingerprint me-1"></i> {{ $label }}</button>
          </form>
        </div></div>
      </div>
      <div class="col-md-5">
        <div class="card h-100"><div class="card-body">
          <div class="text-muted small mb-1">{{ __('Status Today') }}</div>
          @if(! $checkedIn)
            <span class="badge text-bg-secondary">{{ __('Not Clocked In') }}</span>
          @elseif(! $checkedOut)
            <span class="badge text-bg-success">{{ __('On The Clock') }}</span>
          @else
            <span class="badge text-bg-primary">{{ __('Clocked Out') }}</span>
          @endif
          <div class="text-muted small mt-2">Your first punch of the day is the clock-in; the last one is the clock-out.</div>
        </div></div>
      </div>
    </div>

    <div class="card">
      <div class="card-header">{{ __('Recent Attendance') }}</div>
      <div class="card-body p-0">
        <table class="table align-middle mb-0">
          <thead class="table-light"><tr><th>{{ __('Date') }}</th><th>{{ __('Clock In') }}</th><th>{{ __('Clock Out') }}</th><th class="text-center">{{ __('Note') }}</th></tr></thead>
          <tbody>
            @forelse($history as $h)
              <tr>
                <td class="fw-medium">{{ \Illuminate\Support\Carbon::parse($h->date)->format('D, j M Y') }}</td>
                <td>{{ $h->check_in ? \Illuminate\Support\Carbon::parse($h->check_in)->format('g:i a') : '—' }}</td>
                <td>{{ $h->check_out ? \Illuminate\Support\Carbon::parse($h->check_out)->format('g:i a') : '—' }}</td>
                <td class="text-center">
                  @if($h->is_auto_closed)<span class="badge text-bg-warning">{{ __('Auto-closed') }}</span>
                  @elseif($h->is_incomplete)<span class="badge text-bg-danger">{{ __('Incomplete') }}</span>
                  @else <span class="text-muted">—</span>@endif
                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="text-center text-muted py-4">{{ __('No Attendance Recorded Yet.') }}</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  @endif

@endsection
