{{-- Academics Tab --}}
<div class="row g-4">
    {{-- Current Enrollment --}}
    <div class="col-xl-4">
        <x-card title="Current Enrollment" subtitle="Active academic year">
            @if($academic = $student->currentAcademic)
                <dl class="row mb-0">
                    <dt class="col-sm-5 text-muted small">Academic Year</dt>
                    <dd class="col-sm-7 fw-medium">{{ $academic->year->year }}</dd>

                    <dt class="col-sm-5 text-muted small">Class</dt>
                    <dd class="col-sm-7 fw-medium">{{ $academic->class->name }}</dd>

                    <dt class="col-sm-5 text-muted small">Section</dt>
                    <dd class="col-sm-7">{{ $academic->section->name }}</dd>

                    <dt class="col-sm-5 text-muted small">Roll Number</dt>
                    <dd class="col-sm-7">{{ $academic->roll_number }}</dd>

                    <dt class="col-sm-5 text-muted small">Group</dt>
                    <dd class="col-sm-7">{{ $academic->group->name ?? '—' }}</dd>

                    <dt class="col-sm-5 text-muted small">Shift</dt>
                    <dd class="col-sm-7">{{ $academic->shift->name ?? '—' }}</dd>
                </dl>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-mortarboard fs-1 text-slate-300"></i>
                    <p class="mt-2 mb-0">Not currently enrolled</p>
                </div>
            @endif
        </x-card>
    </div>

    {{-- Subject Enrollment --}}
    <div class="col-xl-4">
        <x-card title="Subject Enrollment" subtitle="Current subjects">
            @if(!empty($subjects))
                <div class="list-group list-group-flush">
                    @foreach($subjects as $subject)
                        <div class="list-group-item px-0 border-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-medium">{{ $subject->name }}</div>
                                    <small class="text-muted">{{ $subject->code }} • {{ $subject->type }}</small>
                                </div>
                                <span class="badge bg-{{ $subject->is_compulsory ? 'success' : 'secondary' }}">
                                    {{ $subject->is_compulsory ? 'Compulsory' : 'Optional' }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-book fs-1 text-slate-300"></i>
                    <p class="mt-2 mb-0">No subjects enrolled</p>
                </div>
            @endif
        </x-card>
    </div>

    {{-- Academic History --}}
    <div class="col-xl-4">
        <x-card title="Academic History" subtitle="Previous enrollments">
            @if(!empty($academicHistory))
                <ul class="list-group list-group-flush">
                    @foreach($academicHistory as $history)
                        <li class="list-group-item px-0 border-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-medium">{{ $history->year->year }}</div>
                                    <small class="text-muted">
                                        {{ $history->class->name }} - {{ $history->section->name }}
                                        @if($history->roll_number) • Roll: {{ $history->roll_number }} @endif
                                    </small>
                                </div>
                                @if($history->is_promoted)
                                    <span class="badge bg-success">Promoted</span>
                                @elseif($history->is_repeated)
                                    <span class="badge bg-warning">Repeated</span>
                                @elseif($history->is_transferred)
                                    <span class="badge bg-info">Transferred</span>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="text-center py-4 text-muted">
                    <p class="mb-0">No previous academic records</p>
                </div>
            @endif
        </x-card>
    </div>
</div>

<div class="row g-4 mt-4">
    {{-- Exam Results --}}
    <div class="col-xl-8">
        <x-card title="Exam Results" subtitle="Recent examinations">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Exam</th>
                            <th>Date</th>
                            <th>Subject</th>
                            <th class="text-end">Marks</th>
                            <th>Grade</th>
                            <th class="text-end">Rank</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($examResults as $result)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $result->exam->name }}</div>
                                    <small class="text-muted">{{ $result->examType->name }}</small>
                                </td>
                                <td>{{ $result->exam_date->format('M j, Y') }}</td>
                                <td>{{ $result->subject->name }}</td>
                                <td class="text-end fw-medium">{{ $result->marks_obtained }} / {{ $result->max_marks }}</td>
                                <td>
                                    @if($result->grade)
                                        <span class="badge bg-{{ $result->grade >= 'A' ? 'success' : ($result->grade >= 'B' ? 'info' : ($result->grade >= 'C' ? 'warning' : 'danger')) }}">
                                            {{ $result->grade }}
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($result->rank)
                                        <span class="badge bg-info">{{ $result->rank }}{{ $result->rank === 1 ? 'st' : ($result->rank === 2 ? 'nd' : ($result->rank === 3 ? 'rd' : 'th')) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No exam results found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- Report Cards --}}
    <div class="col-xl-4">
        <x-card title="Report Cards" subtitle="Published reports">
            @if(!empty($reportCards))
                <div class="list-group list-group-flush">
                    @foreach($reportCards as $card)
                        <a href="{{ route('admin.students.report-card', [$student, $card]) }}" class="list-group-item list-group-item-action px-0 border-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-medium">{{ $card->exam->name }}</div>
                                    <small class="text-muted">{{ $card->exam->examType->name }} • {{ $card->created_at->format('M j, Y') }}</small>
                                </div>
                                @if($card->is_published)
                                    <span class="badge bg-success">Published</span>
                                @else
                                    <span class="badge bg-warning">Draft</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-file-earmark-text fs-1 text-slate-300"></i>
                    <p class="mt-2 mb-0">No report cards published</p>
                </div>
            @endif
        </x-card>
    </div>
</div>