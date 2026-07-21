@extends('layouts.admin')
@section('title', 'Application ' . $application->reference_number)
@section('content')
  @php $m = ['submitted'=>'warning','approved'=>'success','rejected'=>'danger']; @endphp
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">{{ __('Home') }}</a></li><li class="breadcrumb-item">{{ __('People') }}</li><li class="breadcrumb-item"><a href="{{ route('admin.admissions.index') }}" class="text-decoration-none">{{ __('Admissions') }}</a></li><li class="breadcrumb-item active">{{ $application->reference_number }}</li></ol></nav>
      <h1 class="h4 mb-0">{{ $application->applicant_name }} <span class="badge text-bg-{{ $m[$application->status] ?? 'secondary' }} align-middle">{{ ucfirst($application->status) }}</span></h1>
    </div>
    @if ($application->status === 'submitted')
      <div class="d-flex gap-2">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#approveModal"><i class="bi bi-check2-all"></i> Approve &amp; enrol</button>
        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal">{{ __('Reject') }}</button>
      </div>
    @endif
  </div>

  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card"><div class="card-header">{{ __('Applicant') }}</div><div class="card-body">
        <dl class="row mb-0">
          <dt class="col-5 text-muted">{{ __('Name') }}</dt><dd class="col-7">{{ $application->applicant_name }}</dd>
          <dt class="col-5 text-muted">{{ __('Gender') }}</dt><dd class="col-7 text-capitalize">{{ $application->gender }}</dd>
          <dt class="col-5 text-muted">{{ __('Date Of Birth') }}</dt><dd class="col-7">{{ optional($application->dob)->format('d M Y') ?? '—' }}</dd>
          <dt class="col-5 text-muted">{{ __('Blood Group') }}</dt><dd class="col-7">{{ $application->blood_group ?? '—' }}</dd>
          <dt class="col-5 text-muted">{{ __('Desired Class') }}</dt><dd class="col-7">{{ $class?->name ?? '—' }}</dd>
          <dt class="col-5 text-muted">{{ __('Academic Year') }}</dt><dd class="col-7">{{ $year?->year ?? '—' }}</dd>
        </dl>
      </div></div>
    </div>
    <div class="col-lg-6">
      <div class="card"><div class="card-header">{{ __('Guardian') }}</div><div class="card-body">
        <dl class="row mb-0">
          <dt class="col-5 text-muted">{{ __('Name') }}</dt><dd class="col-7">{{ $application->guardian_name }}</dd>
          <dt class="col-5 text-muted">{{ __('Relation') }}</dt><dd class="col-7 text-capitalize">{{ str_replace('_', ' ', $application->guardian_relation) }}</dd>
          <dt class="col-5 text-muted">{{ __('Phone') }}</dt><dd class="col-7">{{ $application->guardian_phone }}</dd>
          <dt class="col-5 text-muted">{{ __('Email') }}</dt><dd class="col-7">{{ $application->guardian_email ?? '—' }}</dd>
          @if ($application->decision_reason)<dt class="col-5 text-muted">{{ __('Decision Note') }}</dt><dd class="col-7">{{ $application->decision_reason }}</dd>@endif
        </dl>
      </div></div>
    </div>
  </div>

  @if ($application->status === 'submitted')
    {{-- Approve --}}
    <div class="modal fade" id="approveModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.admissions.approve', $application->id) }}">
        @csrf @method('PATCH')
        <div class="modal-header"><h5 class="modal-title">Approve &amp; enrol</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
          <div class="col-12"><div class="alert alert-info py-2 mb-0 small">{{ __('Enrols Into') }} <strong>{{ $class?->name ?? 'the desired class' }}</strong> {{ __('For') }} <strong>{{ $year?->year ?? 'the desired year' }}</strong>.</div></div>
          <div class="col-md-6"><label class="form-label">{{ __('Admission Number') }} <span class="text-danger">*</span></label>
            <input name="admission_number" class="form-control" required></div>
          <div class="col-md-6"><label class="form-label">{{ __('Section') }} <span class="text-danger">*</span></label>
            <select name="section_id" class="form-select" required>
              <option value="">— select —</option>
              @foreach ($sections as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
            </select>
            @if ($sections->isEmpty())<div class="form-text text-danger">{{ __('No Sections In The Desired Class — Add One First.') }}</div>@endif
          </div>
          <div class="col-md-6"><label class="form-label">{{ __('Roll Number') }}</label>
            <input name="roll_number" class="form-control"></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-success">Approve &amp; enrol</button></div>
      </form>
    </div></div></div>

    {{-- Reject --}}
    <div class="modal fade" id="rejectModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.admissions.reject', $application->id) }}">
        @csrf @method('PATCH')
        <div class="modal-header"><h5 class="modal-title">{{ __('Reject Application') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><label class="form-label">{{ __('Reason') }} <span class="text-danger">*</span></label><textarea name="reason" rows="2" class="form-control" required></textarea></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-danger">{{ __('Reject') }}</button></div>
      </form>
    </div></div></div>
  @endif
@endsection
