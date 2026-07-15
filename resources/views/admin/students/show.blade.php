@extends('layouts.admin')
@section('title', $student->name)
@section('content')

<div class="student-detail">
    {{-- Page Header with Actions --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb" class="mb-2">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin.students.index') }}">Students</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $student->name }}</li>
                </ol>
            </nav>
            <h1 class="h3 mb-1">{{ $student->name }}</h1>
            <p class="text-muted small mb-0">
                Admission No: {{ $student->admission_number }} |
                {{ $student->academic ? $student->academic->class->name . ' - ' . $student->academic->section->name : 'Not enrolled' }}
            </p>
        </div>

        <div class="d-flex gap-2">
            @can('students.edit')
                <a href="{{ route('admin.students.edit', $student) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-pencil me-1"></i> Edit
                </a>
            @endcan
            @can('students.transfer')
                <a href="{{ route('admin.students.transfer', $student) }}" class="btn btn-outline-warning">
                    <i class="bi bi-arrow-left-right me-1"></i> Transfer
                </a>
            @endcan
            <div class="dropdown d-inline-block">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-plus me-1"></i> Actions
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="{{ route('admin.students.create') }}"><i class="bi bi-person-plus me-2"></i> New Student</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.admissions.index') }}"><i class="bi bi-clipboard-check me-2"></i> Admissions</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.students.transfer', $student) }}"><i class="bi bi-arrow-left-right me-2"></i> Transfer</a></li>
                    <li><a class="dropdown-item" href="{{ route('admin.students.deactivate', $student) }}" onclick="return confirm('Deactivate this student?')"><i class="bi bi-person-x me-2 text-danger"></i> Deactivate</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <ul class="nav nav-tabs mb-4" id="studentTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true">
                <i class="bi bi-house me-1"></i> Overview
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="academic-tab" data-bs-toggle="tab" data-bs-target="#academic" type="button" role="tab" aria-controls="academic" aria-selected="false">
                <i class="bi bi-mortarboard me-1"></i> Academic
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab" aria-controls="attendance" aria-selected="false">
                <i class="bi bi-calendar-check me-1"></i> Attendance
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="fees-tab" data-bs-toggle="tab" data-bs-target="#fees" type="button" role="tab" aria-controls="fees" aria-selected="false">
                <i class="bi bi-cash-coin me-1"></i> Fees
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button" role="tab" aria-controls="documents" aria-selected="false">
                <i class="bi bi-file-earmark me-1"></i> Documents
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline" type="button" role="tab" aria-controls="timeline" aria-selected="false">
                <i class="bi bi-clock-history me-1"></i> Timeline
            </button>
        </li>
    </ul>

    {{-- Tab Content --}}
    <div class="tab-content" id="studentTabsContent">
        {{-- Overview Tab --}}
        <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview-tab">
            @include('admin.students.partials.overview')
        </div>

        {{-- Academic Tab --}}
        <div class="tab-pane fade" id="academic" role="tabpanel" aria-labelledby="academic-tab">
            @include('admin.students.partials.academic')
        </div>

        {{-- Attendance Tab --}}
        <div class="tab-pane fade" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
            @include('admin.students.partials.attendance')
        </div>

        {{-- Fees Tab --}}
        <div class="tab-pane fade" id="fees" role="tabpanel" aria-labelledby="fees-tab">
            @include('admin.students.partials.fees')
        </div>

        {{-- Documents Tab --}}
        <div class="tab-pane fade" id="documents" role="tabpanel" aria-labelledby="documents-tab">
            @include('admin.students.partials.documents')
        </div>

        {{-- Timeline Tab --}}
        <div class="tab-pane fade" id="timeline" role="tabpanel" aria-labelledby="timeline-tab">
            @include('admin.students.partials.timeline')
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Persist active tab
    var triggerTabList = [].slice.call(document.querySelectorAll('#studentTabs button'));
    triggerTabList.forEach(function (triggerEl) {
        triggerEl.addEventListener('click', function (event) {
            localStorage.setItem('studentActiveTab', event.target.getAttribute('data-bs-target'));
        });
    });

    // Restore active tab
    var activeTab = localStorage.getItem('studentActiveTab');
    if (activeTab) {
        var triggerEl = document.querySelector('#studentTabs button[data-bs-target="' + activeTab + '"]');
        if (triggerEl) {
            var tab = new bootstrap.Tab(triggerEl);
            tab.show();
        }
    }
});
</script>
@endpush
@endsection