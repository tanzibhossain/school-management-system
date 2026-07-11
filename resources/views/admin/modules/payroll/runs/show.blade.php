@extends('layouts.admin')
@section('title', 'Payroll run')
@section('content')
  @php
    $period = \Carbon\Carbon::create()->month($run->month)->format('F') . ' ' . $run->year;
    $hasEntries = $run->entries->isNotEmpty();
  @endphp
  <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Home</a></li><li class="breadcrumb-item">Payroll</li><li class="breadcrumb-item"><a href="{{ route('admin.payroll.runs.index') }}" class="text-decoration-none">Runs</a></li><li class="breadcrumb-item active">{{ $period }}</li></ol></nav>
      <h1 class="h4 mb-0">Payroll — {{ $period }}
        <span class="badge text-bg-{{ $run->status === 'approved' ? 'success' : ($run->status === 'paid' ? 'primary' : 'secondary') }} align-middle">{{ ucfirst($run->status) }}</span>
      </h1>
    </div>
    <div class="d-flex gap-2">
      @if ($run->status === 'draft')
        <form method="POST" action="{{ route('admin.payroll.runs.process', $run->id) }}" onsubmit="return confirm('Process this run? Entries will be (re)generated from current salary structures.')">@csrf @method('PATCH')<button class="btn btn-primary"><i class="bi bi-gear"></i> {{ $hasEntries ? 'Reprocess' : 'Process' }}</button></form>
        @if ($hasEntries)
          <form method="POST" action="{{ route('admin.payroll.runs.approve', $run->id) }}" onsubmit="return confirm('Approve this run? It becomes final.')">@csrf @method('PATCH')<button class="btn btn-success"><i class="bi bi-check2-all"></i> Approve</button></form>
        @endif
      @endif
    </div>
  </div>

  <div class="card"><div class="card-body">
    @if (! $hasEntries)
      <p class="text-muted mb-0">No entries yet — process the run to generate payslips from each active staff member's salary structure.</p>
    @else
      <table class="table table-hover align-middle w-100 js-dt">
        <thead><tr><th>Employee</th><th>Name</th><th class="text-end">Gross</th><th class="text-end">Deductions</th><th class="text-end">Net</th></tr></thead>
        <tbody>
          @foreach ($run->entries as $e)
            <tr>
              <td><code>{{ $e->staff?->employee_id }}</code></td>
              <td class="fw-semibold">{{ $e->staff?->name ?? '—' }}</td>
              <td class="text-end">{{ number_format((float) $e->gross_salary, 2) }}</td>
              <td class="text-end">{{ number_format((float) $e->total_deductions, 2) }}</td>
              <td class="text-end fw-semibold">{{ number_format((float) $e->net_salary, 2) }}</td>
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr class="table-light"><th colspan="4" class="text-end">Total net</th><th class="text-end">{{ number_format((float) $run->entries->sum('net_salary'), 2) }}</th></tr>
        </tfoot>
      </table>
    @endif
  </div></div>
@endsection
