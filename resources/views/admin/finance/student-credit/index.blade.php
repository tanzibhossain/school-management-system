@extends('layouts.admin')
@section('title', 'Student credit')
@section('content')
  @include('admin.partials.page-header', ['title' => 'Student credit', 'crumbs' => ['Finance', 'Student credit']])

  <form method="GET" class="card mb-3"><div class="card-body row g-2 align-items-end">
    <div class="col-sm-6"><label class="form-label small text-muted mb-1">Student</label>
      <select name="student_id" class="form-select form-select-sm js-select" required>
        <option value="">— select —</option>
        @foreach ($students as $s)<option value="{{ $s->id }}" @selected($student && $student->id === $s->id)>{{ $s->name }} ({{ $s->student_id }})</option>@endforeach
      </select></div>
    <div class="col-sm-3"><button class="btn btn-sm btn-primary">View ledger</button></div>
  </div></form>

  @if ($student)
    <div class="row g-3 mb-3">
      <div class="col-md-4"><div class="card"><div class="card-body">
        <div class="text-muted small">Balance — {{ $student->name }}</div>
        <div class="h3 mb-0">{{ number_format($balance, 2) }}</div>
      </div></div></div>
      <div class="col-md-8"><div class="card"><div class="card-body">
        <form method="POST" action="{{ route('admin.student-credit.adjust') }}" class="row g-2 align-items-end">
          @csrf
          <input type="hidden" name="student_id" value="{{ $student->id }}">
          <div class="col-sm-3"><label class="form-label small text-muted mb-1">Direction</label>
            <select name="direction" class="form-select form-select-sm"><option value="credit">Credit (+)</option><option value="debit">Debit (−)</option></select></div>
          <div class="col-sm-3"><label class="form-label small text-muted mb-1">Amount</label>
            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required></div>
          <div class="col-sm-4"><label class="form-label small text-muted mb-1">Note</label>
            <input name="note" class="form-control form-control-sm"></div>
          <div class="col-sm-2"><button class="btn btn-sm btn-outline-primary w-100">Apply</button></div>
        </form>
      </div></div></div>
    </div>

    <div class="card"><div class="card-body">
      @if ($transactions->isEmpty())
        <p class="text-muted mb-0">No credit transactions.</p>
      @else
        <table class="table table-hover align-middle w-100 js-dt">
          <thead><tr><th>Date</th><th>Type</th><th class="text-end">Amount</th><th>Reference</th><th>Note</th></tr></thead>
          <tbody>
            @foreach ($transactions as $t)
              <tr>
                <td class="small">{{ $t->created_at?->format('d M Y H:i') }}</td>
                <td>
                  @php $m = ['credit'=>'success','debit'=>'danger','refund'=>'info']; @endphp
                  <span class="badge text-bg-{{ $m[$t->type] ?? 'secondary' }}">{{ ucfirst($t->type) }}</span>
                </td>
                <td class="text-end">{{ number_format((float) $t->amount, 2) }}</td>
                <td class="small">{{ $t->reference_type ?? '—' }}{{ $t->reference_id ? ' #' . $t->reference_id : '' }}</td>
                <td>{{ $t->note ?? '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div></div>
  @else
    <div class="alert alert-info">Select a student to view their credit ledger.</div>
  @endif
@endsection
