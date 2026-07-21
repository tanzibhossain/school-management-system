{{-- Attendance Tab --}}
<div class="row g-4">
    {{-- Attendance Summary --}}
    <div class="col-xl-4">
        <x-card title="{{ __('Attendance Summary') }}" subtitle="This academic year">
            <div class="row g-3 text-center mb-3">
                <div class="col-4">
                    <div class="p-3 bg-success-light text-success rounded">
                        <div class="h3 fw-bold mb-0">{{ $attendanceStats->present ?? 0 }}</div>
                        <div class="text-xs text-muted">{{ __('Present') }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 bg-danger-light text-danger rounded">
                        <div class="h3 fw-bold mb-0">{{ $attendanceStats->absent ?? 0 }}</div>
                        <div class="text-xs text-muted">{{ __('Absent') }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 bg-warning-light text-warning rounded">
                        <div class="h3 fw-bold mb-0">{{ $attendanceStats->late ?? 0 }}</div>
                        <div class="text-xs text-muted">{{ __('Late') }}</div>
                    </div>
                </div>
            </div>

            <hr>

            <div class="text-center">
                <div class="h2 fw-bold text-primary mb-1">{{ $attendanceRate ?? 0 }}%</div>
                <div class="text-muted small">{{ __('Overall Attendance Rate') }}</div>
            </div>

            <hr>

            <div class="d-grid gap-2">
                <a href="{{ route('admin.attendance.index', ['student' => $student->id]) }}" class="btn btn-outline-primary">
                    <i class="bi bi-calendar-check me-1"></i> View Full Calendar
                </a>
                <a href="{{ route('admin.attendance.report', ['student' => $student->id]) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Attendance Report
                </a>
            </div>
        </x-card>
    </div>

    {{-- Monthly Attendance Chart --}}
    <div class="col-xl-8">
        <x-card title="{{ __('Monthly Attendance') }}" subtitle="Attendance percentage by month">
            <div class="chart-container" style="height: 300px;">
                <canvas id="monthlyAttendanceChart"></canvas>
            </div>
        </x-card>
    </div>
</div>

<div class="row g-4 mt-4">
    {{-- Recent Attendance --}}
    <div class="col-12">
        <x-card title="{{ __('Recent Attendance') }}" subtitle="Last 30 days">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Remarks') }}</th>
                            <th class="text-end">{{ __('Recorded By') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentAttendance as $record)
                            <tr>
                                <td>{{ $record->date->format('M j, Y') }}</td>
                                <td>
                                    <span class="badge {{ $record->status === 'present' ? 'bg-success' : ($record->status === 'absent' ? 'bg-danger' : ($record->status === 'late' ? 'bg-warning' : 'bg-info')) }}">
                                        {{ ucfirst($record->status) }}
                                    </span>
                                </td>
                                <td>{{ $record->remarks ?? '—' }}</td>
                                <td class="text-end text-muted small">{{ $record->recordedBy->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">{{ __('No Attendance Records Found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Attendance Chart
    new Chart(document.getElementById('monthlyAttendanceChart'), {
        type: 'line',
        data: {
            labels: @json(array_column($monthlyAttendance, 'month')),
            datasets: [{
                label: 'Present %',
                data: @json(array_column($monthlyAttendance, 'present_pct')),
                borderColor: 'rgb(37, 99, 235)',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
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
                y: { beginAtZero: true, max: 100, grid: { color: 'rgba(148, 163, 184, 0.1)' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>
@endpush