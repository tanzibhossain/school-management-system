@extends('layouts.portal')
@section('title', 'Attendance')
@section('heading', 'Attendance')
@section('content')

  <div class="row g-3 mb-3">
    <div class="col-sm-4">
      <div class="card"><div class="card-body">
        <div class="text-muted small mb-1">Attendance rate</div>
        <div class="h4 mb-0">{{ $summary['percent'] !== null ? $summary['percent'] . '%' : '—' }}</div>
      </div></div>
    </div>
    <div class="col-sm-4">
      <div class="card"><div class="card-body">
        <div class="text-muted small mb-1">Days present</div>
        <div class="h4 mb-0">{{ $summary['present'] }}</div>
      </div></div>
    </div>
    <div class="col-sm-4">
      <div class="card"><div class="card-body">
        <div class="text-muted small mb-1">Days recorded</div>
        <div class="h4 mb-0">{{ $summary['total'] }}</div>
      </div></div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Attendance history</div>
    <div class="card-body p-0">
      <table class="table align-middle mb-0">
        <thead class="table-light"><tr><th>Date</th><th>Status</th></tr></thead>
        <tbody>
          @forelse($records as $r)
            @php
              $badge = match($r->status) {
                'present' => 'text-bg-success', 'late' => 'text-bg-warning',
                'half_day' => 'text-bg-info', 'leave' => 'text-bg-secondary', default => 'text-bg-danger',
              };
            @endphp
            <tr>
              <td>{{ \Illuminate\Support\Carbon::parse($r->date)->format('D, j M Y') }}</td>
              <td><span class="badge {{ $badge }}">{{ ucfirst(str_replace('_', ' ', $r->status)) }}</span></td>
            </tr>
          @empty
            <tr><td colspan="2" class="text-center text-muted py-4">No attendance recorded yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($records)<div class="mt-3">{{ $records->appends(['student' => $student->id])->links() }}</div>@endif

@endsection
