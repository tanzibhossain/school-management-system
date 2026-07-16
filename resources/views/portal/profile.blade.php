@extends('layouts.portal')
@section('title', 'Profile')
@section('heading', 'Profile')
@section('content')

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card"><div class="card-body text-center">
        <span class="avatar-sm mx-auto mb-3" style="width:72px;height:72px;font-size:2rem;">{{ strtoupper(substr($student->name, 0, 1)) }}</span>
        <h5 class="mb-0">{{ $student->name }}</h5>
        <div class="text-muted small">
          @if($enrollment){{ $enrollment->schoolClass->name ?? '' }} · Section {{ $enrollment->section->name ?? '' }}@endif
        </div>
        <div class="mt-2"><span class="badge text-bg-light">{{ $student->admission_number }}</span></div>
      </div></div>
    </div>
    <div class="col-lg-8">
      <div class="card mb-3"><div class="card-header">Student details</div><div class="card-body">
        <div class="row g-3">
          @foreach([
            'Admission no.' => $student->admission_number,
            'Student ID'    => $student->student_id,
            'Class'         => $enrollment->schoolClass->name ?? '—',
            'Section'       => $enrollment->section->name ?? '—',
            'Shift'         => $enrollment->shift->name ?? '—',
            'Roll'          => $enrollment->roll_number ?? '—',
            'Gender'        => ucfirst($student->gender ?? '—'),
            'Date of birth' => optional($student->dob)->format('j M Y') ?? '—',
          ] as $label => $value)
            <div class="col-sm-6"><div class="text-muted small">{{ $label }}</div><div class="fw-medium">{{ $value }}</div></div>
          @endforeach
        </div>
      </div></div>

      <div class="card"><div class="card-header">Guardians</div><div class="card-body p-0">
        <table class="table align-middle mb-0">
          <thead class="table-light"><tr><th>Name</th><th>Relation</th><th>Phone</th></tr></thead>
          <tbody>
            @forelse($guardians as $g)
              <tr>
                <td class="fw-medium">{{ $g->name }} @if($g->is_primary)<span class="badge text-bg-light ms-1">Primary</span>@endif</td>
                <td>{{ ucfirst(str_replace('_', ' ', $g->relation)) }}</td>
                <td>{{ $g->phone ?? '—' }}</td>
              </tr>
            @empty
              <tr><td colspan="3" class="text-center text-muted py-3">No guardians on record.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div></div>
    </div>
  </div>

@endsection
