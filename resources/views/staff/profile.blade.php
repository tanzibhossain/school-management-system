@extends('layouts.staff')
@section('title', __('My Profile'))
@section('heading', 'My Profile')
@section('content')

  @if(! $staff)
    <div class="alert alert-info">{{ __('No Staff Record Is Linked To Your Account Yet. Please Contact The Administrator.') }}</div>
  @else
    <div class="row g-3">
      <div class="col-lg-4">
        <div class="card"><div class="card-body text-center">
          <span class="avatar-sm mx-auto mb-3" style="width:72px;height:72px;font-size:2rem;">{{ strtoupper(substr($staff->name, 0, 1)) }}</span>
          <h5 class="mb-0">{{ $staff->name }}</h5>
          <div class="text-muted small">{{ $staff->designation->name ?? 'Staff' }}</div>
          <div class="mt-2"><span class="badge text-bg-light">{{ $staff->employee_id }}</span></div>
        </div></div>
      </div>
      <div class="col-lg-8">
        <div class="card"><div class="card-body">
          <div class="row g-3">
            @foreach([
              'Department'       => $staff->department->name ?? '—',
              'Designation'      => $staff->designation->name ?? '—',
              'Teaching subject' => $staff->subject->name ?? '—',
              'Gender'           => ucfirst($staff->gender ?? '—'),
              'Employment type'  => ucfirst(str_replace('_', ' ', $staff->employment_type ?? '—')),
              'Joining date'     => optional($staff->joining_date)->format('j M Y') ?? '—',
              'RFID number'      => $staff->rfid_number ?? '—',
              'Status'           => ucfirst($staff->status ?? '—'),
            ] as $label => $value)
              <div class="col-sm-6">
                <div class="text-muted small">{{ $label }}</div>
                <div class="fw-medium">{{ $value }}</div>
              </div>
            @endforeach
          </div>
        </div></div>
      </div>
    </div>
  @endif

@endsection
