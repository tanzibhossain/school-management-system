@extends('layouts.admin')
@section('title', 'Payroll runs')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Payroll runs',
    'crumbs' => ['Payroll', 'Runs'],
    'action' => ['label' => 'New run', 'modal' => 'createModal'],
  ])
  @include('admin.modules.payroll._tabs', ['active' => 'runs'])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>Period</th><th>Status</th><th>Entries</th><th class="text-end" data-orderable="false">Actions</th></tr></thead>
      <tbody>
        @foreach ($runs as $r)
          <tr>
            <td class="fw-semibold">{{ \Carbon\Carbon::create()->month($r->month)->format('F') }} {{ $r->year }}</td>
            <td><span class="badge text-bg-{{ $r->status === 'approved' ? 'success' : ($r->status === 'paid' ? 'primary' : 'secondary') }}">{{ ucfirst($r->status) }}</span></td>
            <td>{{ $r->entries_count }}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.payroll.runs.show', $r->id) }}">Open</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.payroll.runs.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">New payroll run</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-md-6"><label class="form-label">Month <span class="text-danger">*</span></label>
          <select name="month" class="form-select" required>
            @for ($m = 1; $m <= 12; $m++)<option value="{{ $m }}" @selected(now()->month == $m)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>@endfor
          </select></div>
        <div class="col-md-6"><label class="form-label">Year <span class="text-danger">*</span></label>
          <input type="number" min="2000" max="2100" name="year" class="form-control" value="{{ now()->year }}" required></div>
        <div class="col-12"><label class="form-label">Notes</label><input name="notes" class="form-control"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create</button></div>
    </form>
  </div></div></div>
@endsection
