{{-- Student Overview Tab --}}
@php
    $academic = $student->currentAcademic;
    $guardian = $student->guardians->first();
@endphp

<div class="row g-4">
    {{-- Personal Info Card --}}
    <div class="col-xl-4">
        <x-card title="{{ __('Personal Information') }}" subtitle="Basic details">
            <dl class="row mb-0">
                <dt class="col-sm-4 text-muted small">{{ __('Admission Number') }}</dt>
                <dd class="col-sm-8 fw-medium">{{ $student->admission_number }}</dd>

                <dt class="col-sm-4 text-muted small">{{ __('Date of Birth') }}</dt>
                <dd class="col-sm-8">{{ $student->dob?->format('M j, Y') }}</dd>

                <dt class="col-sm-4 text-muted small">{{ __('Gender') }}</dt>
                <dd class="col-sm-8">{{ ucfirst($student->gender) }}</dd>

                <dt class="col-sm-4 text-muted small">{{ __('Blood Group') }}</dt>
                <dd class="col-sm-8">{{ $student->blood_group ?? '—' }}</dd>

                <dt class="col-sm-4 text-muted small">{{ __('Religion') }}</dt>
                <dd class="col-sm-8">{{ $student->religion }}</dd>

                <dt class="col-sm-4 text-muted small">{{ __('Category') }}</dt>
                <dd class="col-sm-8">{{ $student->category ?? '—' }}</dd>

                <dt class="col-sm-4 text-muted small">{{ __('Nationality') }}</dt>
                <dd class="col-sm-8">{{ $student->nationality ?? '—' }}</dd>

                <dt class="col-sm-4 text-muted small">{{ __('Mother Tongue') }}</dt>
                <dd class="col-sm-8">{{ $student->mother_tongue ?? '—' }}</dd>
            </dl>
        </x-card>
    </div>

    {{-- Contact Info Card --}}
    <div class="col-xl-4">
        <x-card title="{{ __('Contact Information') }}" subtitle="Address & phone">
            <dl class="row mb-0">
                <dt class="col-sm-4 text-muted small">{{ __('Phone') }}</dt>
                <dd class="col-sm-8">{{ $student->phone ?? '—' }}</dd>

                <dt class="col-sm-4 text-muted small">{{ __('Email') }}</dt>
                <dd class="col-sm-8">{{ $student->email ?? '—' }}</dd>

                <dt class="col-sm-4 text-muted small">{{ __('Present Address') }}</dt>
                <dd class="col-sm-8">{{ $student->present_address ?? '—' }}</dd>

                <dt class="col-sm-4 text-muted small">{{ __('Permanent Address') }}</dt>
                <dd class="col-sm-8">{{ $student->permanent_address ?? 'Same as present' }}</dd>

                <dt class="col-sm-4 text-muted small">{{ __('Admission Date') }}</dt>
                <dd class="col-sm-8">{{ $student->admission_date?->format('M j, Y') }}</dd>
            </dl>
        </x-card>
    </div>

    {{-- Guardian Card --}}
    <div class="col-xl-4">
        <x-card title="{{ __('Guardian Information') }}" subtitle="Primary contact">
            @if($guardian)
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted small">{{ __('Name') }}</dt>
                    <dd class="col-sm-8 fw-medium">{{ $guardian->name }}</dd>

                    <dt class="col-sm-4 text-muted small">{{ __('Relation') }}</dt>
                    <dd class="col-sm-8">{{ ucfirst($guardian->relation) }}</dd>

                    <dt class="col-sm-4 text-muted small">{{ __('Phone') }}</dt>
                    <dd class="col-sm-8">{{ $guardian->phone }}</dd>

                    <dt class="col-sm-4 text-muted small">{{ __('Email') }}</dt>
                    <dd class="col-sm-8">{{ $guardian->email ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted small">{{ __('Occupation') }}</dt>
                    <dd class="col-sm-8">{{ $guardian->occupation ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted small">{{ __('Address') }}</dt>
                    <dd class="col-sm-8">{{ $guardian->address ?? '—' }}</dd>
                </dl>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-person-x fs-1 text-slate-300"></i>
                    <p class="mt-2 mb-0">{{ __('No guardian information') }}</p>
                </div>
            @endif
        </x-card>
    </div>

    {{-- Current Academic Status --}}
    <div class="col-xl-8">
        <x-card title="{{ __('Current Academic Status') }}" subtitle="Enrollment details">
            @if($academic)
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary-light text-primary p-3 rounded">
                            <div class="text-xs text-muted">{{ __('Academic Year') }}</div>
                            <div class="fw-bold">{{ $academic->academicYear->year ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success-light text-success p-3 rounded">
                            <div class="text-xs text-muted">{{ __('Class') }}</div>
                            <div class="fw-bold">{{ $academic->class->name ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning-light text-warning p-3 rounded">
                            <div class="text-xs text-muted">{{ __('Section') }}</div>
                            <div class="fw-bold">{{ $academic->section->name ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-info-light text-info p-3 rounded">
                            <div class="text-xs text-muted">{{ __('Roll Number') }}</div>
                            <div class="fw-bold">{{ $academic->roll_number ?? '—' }}</div>
                        </div>
                    </div>
                </div>

                @if($academic->subjects->count())
                    <hr class="my-3">
                    <h6 class="text-muted small mb-3">{{ __('Current Subjects') }}</h6>
                    <div class="row g-2">
                        @foreach($academic->subjects as $subject)
                            <div class="col-md-4">
                                <span class="badge bg-slate-100 text-slate-700 px-3 py-2 d-block text-center">
                                    {{ $subject->name }}
                                    @if($subject->pivot->is_optional) <span class="ms-1 badge bg-warning text-dark">{{ __('Optional') }}</span> @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-mortarboard fs-1 text-slate-300"></i>
                    <p class="mt-2 mb-0">{{ __('Not currently enrolled') }}</p>
                </div>
            @endif
        </x-card>
    </div>

    {{-- Quick Stats --}}
    <div class="col-xl-4">
        <x-card title="{{ __('Quick Statistics') }}" subtitle="This academic year">
            <div class="row g-3 text-center">
                <div class="col-6">
                    <div class="p-3 bg-slate-50 rounded">
                        <div class="h4 fw-bold text-primary mb-0">{{ $student->attendancePercentage ?? 0 }}%</div>
                        <div class="text-xs text-muted">{{ __('Attendance') }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-slate-50 rounded">
                        <div class="h4 fw-bold text-success mb-0">{{ $student->feeBalance ?? 0 }}</div>
                        <div class="text-xs text-muted">{{ __('Fee Balance') }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-slate-50 rounded">
                        <div class="h4 fw-bold text-info mb-0">{{ $student->booksIssued ?? 0 }}</div>
                        <div class="text-xs text-muted">{{ __('Books Issued') }}</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-slate-50 rounded">
                        <div class="h4 fw-bold text-warning mb-0">{{ $student->pendingLeaves ?? 0 }}</div>
                        <div class="text-xs text-muted">{{ __('Pending Leaves') }}</div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>
</div>