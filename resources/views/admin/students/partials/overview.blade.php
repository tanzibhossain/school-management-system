{{-- Student Overview Tab --}}
@php
    $academic = $student->currentAcademic;
    $guardian = $student->guardians->first();
@endphp

<div class="row g-4">
    {{-- Personal Info Card --}}
    <div class="col-xl-4">
        <x-card title="Personal Information" subtitle="Basic details">
            <dl class="row mb-0">
                <dt class="col-sm-4 text-muted small">Admission Number</dt>
                <dd class="col-sm-8 fw-medium">{{ $student->admission_number }}</dd>

                <dt class="col-sm-4 text-muted small">Date of Birth</dt>
                <dd class="col-sm-8">{{ $student->dob?->format('M j, Y') }}</dd>

                <dt class="col-sm-4 text-muted small">Gender</dt>
                <dd class="col-sm-8">{{ ucfirst($student->gender) }}</dd>

                <dt class="col-sm-4 text-muted small">Blood Group</dt>
                <dd class="col-sm-8">{{ $student->blood_group ?? '—' }}</dd>

                <dt class="col-sm-4 text-muted small">Religion</dt>
                <dd class="col-sm-8">{{ $student->religion }}</dd>

                <dt class="col-sm-4 text-muted small">Category</dt>
                <dd class="col-sm-8">{{ $student->category ?? '—' }}</dd>

                <dt class="col-sm-4 text-muted small">Nationality</dt>
                <dd class="col-sm-8">{{ $student->nationality ?? '—' }}</dd>

                <dt class="col-sm-4 text-muted small">Mother Tongue</dt>
                <dd class="col-sm-8">{{ $student->mother_tongue ?? '—' }}</dd>
            </dl>
        </x-card>
    </div>

    {{-- Contact Info Card --}}
    <div class="col-xl-4">
        <x-card title="Contact Information" subtitle="Address & phone">
            <dl class="row mb-0">
                <dt class="col-sm-4 text-muted small">Phone</dt>
                <dd class="col-sm-8">{{ $student->phone ?? '—' }}</dd>

                <dt class="col-sm-4 text-muted small">Email</dt>
                <dd class="col-sm-8">{{ $student->email ?? '—' }}</dd>

                <dt class="col-sm-4 text-muted small">Present Address</dt>
                <dd class="col-sm-8">{{ $student->present_address ?? '—' }}</dd>

                <dt class="col-sm-4 text-muted small">Permanent Address</dt>
                <dd class="col-sm-8">{{ $student->permanent_address ?? 'Same as present' }}</dd>

                <dt class="col-sm-4 text-muted small">Admission Date</dt>
                <dd class="col-sm-8">{{ $student->admission_date?->format('M j, Y') }}</dd>
            </dl>
        </x-card>
    </div>

    {{-- Guardian Card --}}
    <div class="col-xl-4">
        <x-card title="Guardian Information" subtitle="Primary contact">
            @if($guardian)
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-muted small">Name</dt>
                    <dd class="col-sm-8 fw-medium">{{ $guardian->name }}</dd>

                    <dt class="col-sm-4 text-muted small">Relation</dt>
                    <dd class="col-sm-8">{{ ucfirst($guardian->relation) }}</dd>

                    <dt class="col-sm-4 text-muted small">Phone</dt>
                    <dd class="col-sm-8">{{ $guardian->phone }}</dd>

                    <dt class="col-sm-4 text-muted small">Email</dt>
                    <dd class="col-sm-8">{{ $guardian->email ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted small">Occupation</dt>
                    <dd class="col-sm-8">{{ $guardian->occupation ?? '—' }}</dd>

                    <dt class="col-sm-4 text-muted small">Address</dt>
                    <dd class="col-sm-8">{{ $guardian->address ?? '—' }}</dd>
                </dl>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-person-x fs-1 text-slate-300"></i>
                    <p class="mt-2 mb-0">No guardian information</p>
                </div>
            @endif
        </x-card>
    </div>

    {{-- Current Academic Status --}}
    <div class="col-xl-8">
        <x-card title="Current Academic Status" subtitle="Enrollment details">
            @if($academic)
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="stat-card bg-primary-light text-primary p-3 rounded">
                            <div class="text-xs text-muted">Academic Year</div>
                            <div class="fw-bold">{{ $academic->academicYear->year ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-success-light text-success p-3 rounded">
                            <div class="text-xs text-muted">Class</div>
                            <div class="fw-bold">{{ $academic->class->name ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-warning-light text-warning p-3 rounded">
                            <div class="text-xs text-muted">Section</div>
                            <div class="fw-bold">{{ $academic->section->name ?? '—' }}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card bg-info-light text-info p-3 rounded">
                            <div class="text-xs text-muted">Roll Number</div>
                            <div class="fw-bold">{{ $academic->roll_number ?? '—' }}</div>
                        </div>
                    </div>
                </div>

                @if($academic->subjects->count())
                    <hr class="my-3">
                    <h6 class="text-muted small mb-3">Current Subjects</h6>
                    <div class="row g-2">
                        @foreach($academic->subjects as $subject)
                            <div class="col-md-4">
                                <span class="badge bg-slate-100 text-slate-700 px-3 py-2 d-block text-center">
                                    {{ $subject->name }}
                                    @if($subject->pivot->is_optional) <span class="ms-1 badge bg-warning text-dark">Optional</span> @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-mortarboard fs-1 text-slate-300"></i>
                    <p class="mt-2 mb-0">Not currently enrolled</p>
                </div>
            @endif
        </x-card>
    </div>

    {{-- Quick Stats --}}
    <div class="col-xl-4">
        <x-card title="Quick Statistics" subtitle="This academic year">
            <div class="row g-3 text-center">
                <div class="col-6">
                    <div class="p-3 bg-slate-50 rounded">
                        <div class="h4 fw-bold text-primary mb-0">{{ $student->attendancePercentage ?? 0 }}%</div>
                        <div class="text-xs text-muted">Attendance</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-slate-50 rounded">
                        <div class="h4 fw-bold text-success mb-0">{{ $student->feeBalance ?? 0 }}</div>
                        <div class="text-xs text-muted">Fee Balance</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-slate-50 rounded">
                        <div class="h4 fw-bold text-info mb-0">{{ $student->booksIssued ?? 0 }}</div>
                        <div class="text-xs text-muted">Books Issued</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-slate-50 rounded">
                        <div class="h4 fw-bold text-warning mb-0">{{ $student->pendingLeaves ?? 0 }}</div>
                        <div class="text-xs text-muted">Pending Leaves</div>
                    </div>
                </div>
            </div>
        </x-card>
    </div>
</div>