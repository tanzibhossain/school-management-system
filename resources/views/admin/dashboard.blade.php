@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')

{{-- Page Header --}}
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h3 mb-1 page-title">Dashboard</h1>
        <p class="text-muted small mb-0">Welcome back, {{ auth()->user()->name }}. Here's what's happening today.</p>
    </div>
    <div class="d-flex gap-2">
        <x-button variant="ghost" size="sm" icon="bi-download" icon-position="right" @click="exportDashboard">Export Report</x-button>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <x-card class="h-100" variant="default" padding="md">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Active Students</p>
                    <h3 class="mb-0">{{ number_format($totalStudents) }}</h3>
                    <span class="text-success small"><i class="bi bi-arrow-up-right"></i> {{ $pendingAdmissions }} pending admissions</span>
                </div>
                <div class="stat-icon bg-primary-light text-primary rounded-3 p-3">
                    <i class="bi bi-people-fill fs-4"></i>
                </div>
            </div>
        </x-card>
    </div>

    <div class="col-xl-3 col-md-6">
        <x-card class="h-100" variant="default" padding="md">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Active Staff</p>
                    <h3 class="mb-0">{{ number_format($totalStaff) }}</h3>
                    <span class="text-muted small">Teachers & admin</span>
                </div>
                <div class="stat-icon bg-success-light text-success rounded-3 p-3">
                    <i class="bi bi-person-badge-fill fs-4"></i>
                </div>
            </div>
        </x-card>
    </div>

    <div class="col-xl-3 col-md-6">
        <x-card class="h-100" variant="default" padding="md">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Revenue This Month</p>
                    <h3 class="mb-0">{{ $revenueThisMonth ? number_format($revenueThisMonth, 0) : '0' }}</h3>
                    <span class="text-muted small">Outstanding: {{ number_format($outstandingDues, 0) }}</span>
                </div>
                <div class="stat-icon bg-warning-light text-warning rounded-3 p-3">
                    <i class="bi bi-currency-dollar fs-4"></i>
                </div>
            </div>
        </x-card>
    </div>

    <div class="col-xl-3 col-md-6">
        <x-card class="h-100" variant="default" padding="md">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <p class="text-muted small mb-1">Attendance Today</p>
                    <h3 class="mb-0">{{ $attendanceRate }}%</h3>
                    <span class="text-muted small">{{ $totalEnrolled }} enrolled</span>
                </div>
                <div class="stat-icon bg-info-light text-info rounded-3 p-3">
                    <i class="bi bi-calendar-check-fill fs-4"></i>
                </div>
            </div>
        </x-card>
    </div>
</div>

{{-- Charts Row --}}
<div class="row g-3 mb-4">
    <!-- Revenue Chart -->
    <div class="col-xl-8">
        <x-card title="Revenue Trend (Last 6 Months)" subtitle="Monthly revenue collection" class="h-100">
            <div class="chart-container" style="height: 300px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </x-card>
    </div>

    <!-- Class Strength -->
    <div class="col-xl-4">
        <x-card title="Class Strength" subtitle="Active students per class" class="h-100">
            <div class="chart-container" style="height: 300px;">
                <canvas id="classStrengthChart"></canvas>
            </div>
        </x-card>
    </div>
</div>

<!-- Second Charts Row -->
<div class="row g-3 mb-4">
    <!-- Attendance Trend -->
    <div class="col-xl-6">
        <x-card title="Attendance Trend (Last 7 Days)" subtitle="Daily present count" class="h-100">
            <div class="chart-container" style="height: 280px;">
                <canvas id="attendanceChart"></canvas>
            </div>
        </x-card>
    </div>

    <!-- Class Strength Bar Chart -->
    <div class="col-xl-6">
        <x-card title="Fee Defaulters" subtitle="Top 5 students with overdue fees" class="h-100">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th class="text-end">Overdue</th>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feeDefaulters as $student)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $student->name }}</div>
                                <small class="text-muted">{{ $student->admission_number }}</small>
                            </td>
                            <td class="text-end">
                                <span class="badge badge-danger">{{ $student->overdue_count }} invoices</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center py-4 text-muted">No fee defaulters</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>
</div>

<!-- Bottom Row: Recent Activity & Upcoming -->
<div class="row g-3">
    <!-- Recent Students -->
    <div class="col-xl-6">
        <x-card title="Recent Students" subtitle="Latest enrollments">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Admission No.</th>
                            <th class="text-end">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentStudents as $student)
                            <tr>
                                <td>{{ $student->name }}</td>
                                <td>{{ $student->admission_number }}</td>
                                <td class="text-end text-muted small">{{ $student->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">No recent students</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    <!-- Upcoming Exams -->
    <div class="col-xl-6">
        <x-card title="Upcoming Exams" subtitle="Published exams starting soon">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Exam</th>
                            <th class="text-end">Period</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($upcomingExams as $exam)
                            <tr>
                                <td>{{ $exam->name }}</td>
                                <td class="text-end text-muted small">
                                    {{ $exam->start_date->format('M j') }} - {{ $exam->end_date->format('M j, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center py-4 text-muted">No upcoming exams</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>

<!-- Pending Admissions Alert -->
@if($pendingAdmissions > 0)
<div class="row g-3 mt-3">
    <div class="col-12">
        <x-alert variant="warning" :dismissible="false" icon="true" title="Pending Admissions">
            There are <strong>{{ $pendingAdmissions }}</strong> admission applications awaiting review.
            <a href="{{ route('admin.admissions.index') }}" class="btn btn-sm btn-outline-warning ms-2">Review Now</a>
        </x-alert>
    </div>
</div>
@endif

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: @json(array_column($revenueChart, 'month')),
            datasets: [{
                label: 'Revenue',
                data: @json(array_column($revenueChart, 'amount')),
                borderColor: 'rgb(79, 70, 229)',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Class Strength - Doughnut
    new Chart(document.getElementById('classStrengthChart'), {
        type: 'doughnut',
        data: {
            labels: @json(array_column($classStrength->toArray(), 'class')),
            datasets: [{
                data: @json(array_column($classStrength->toArray(), 'count')),
                backgroundColor: [
                    'rgba(79, 70, 229, 0.8)',
                    'rgba(5, 150, 105, 0.8)',
                    'rgba(217, 119, 6, 0.8)',
                    'rgba(220, 38, 38, 0.8)',
                    'rgba(139, 92, 246, 0.8)',
                    'rgba(20, 184, 166, 0.8)',
                    'rgba(236, 72, 153, 0.8)',
                    'rgba(107, 114, 128, 0.8)',
                ],
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true } } },
            cutout: '65%',
        }
    });

    // Attendance Trend - Bar
    new Chart(document.getElementById('attendanceChart'), {
        type: 'bar',
        data: {
            labels: @json(array_column($attendanceTrend, 'date')),
            datasets: [{
                label: 'Present',
                data: @json(array_column($attendanceTrend, 'present')),
                backgroundColor: 'rgba(5, 150, 105, 0.8)',
                borderRadius: 6,
                maxBarThickness: 40,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endpush
@endsection