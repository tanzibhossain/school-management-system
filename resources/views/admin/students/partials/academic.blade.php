{{-- Academic Tab --}}
<div class="row g-4">
    {{-- Academic History --}}
    <div class="col-xl-8">
        <x-card title="{{ __('Academic History') }}" subtitle="Enrollment records">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('Academic Year') }}</th>
                            <th>{{ __('Class') }}</th>
                            <th>{{ __('Section') }}</th>
                            <th>{{ __('Group') }}</th>
                            <th>{{ __('Roll No.') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th class="text-end">{{ __('Promoted') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($student->academics as $academic)
                            <tr>
                                <td>{{ $academic->academicYear->year }}</td>
                                <td>{{ $academic->class->name }}</td>
                                <td>{{ $academic->section->name }}</td>
                                <td>{{ $academic->group->name ?? '—' }}</td>
                                <td>{{ $academic->roll_number ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $academic->is_current ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $academic->is_current ? 'Current' : 'Completed' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if($academic->promoted_at)
                                        <span class="text-muted small">{{ $academic->promoted_at->format('M j, Y') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">{{ __('No Academic Records Found') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- Current Subjects --}}
    <div class="col-xl-4">
        <x-card title="{{ __('Current Subjects') }}" subtitle="This academic year">
            @if($student->currentAcademic && $student->currentAcademic->subjects->count())
                <div class="row g-2">
                    @foreach($student->currentAcademic->subjects as $subject)
                        <div class="col-md-6">
                            <span class="badge bg-{{ $subject->pivot->is_optional ? 'warning' : 'primary-light' }} text-{{ $subject->pivot->is_optional ? 'dark' : 'white' }} px-3 py-2 d-block text-center w-100">
                                {{ $subject->name }}
                                @if($subject->pivot->is_optional)
                                    <span class="ms-1 badge bg-dark text-white">{{ __('Optional') }}</span>
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-book fs-1 text-slate-300"></i>
                    <p class="mt-2 mb-0">{{ __('No Subjects Assigned') }}</p>
                </div>
            @endif
        </x-card>
    </div>
</div>