@extends('layouts.admin')
@section('title', 'SMS batch #' . $batch->id)
@section('content')
  @php $m = ['queued'=>'secondary','processing'=>'info','completed'=>'success','failed'=>'danger']; @endphp
  @include('admin.partials.page-header', ['title' => 'SMS batch #' . $batch->id, 'crumbs' => ['Comms', 'SMS', 'Batch #' . $batch->id]])

  <div class="mb-3"><a href="{{ route('admin.sms.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Back to SMS</a></div>

  <div class="card mb-4"><div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3 text-muted">Status</dt><dd class="col-sm-9"><span class="badge text-bg-{{ $m[$batch->status] ?? 'secondary' }}">{{ ucfirst($batch->status) }}</span></dd>
      <dt class="col-sm-3 text-muted">Scope</dt><dd class="col-sm-9 text-capitalize">{{ $batch->scope }}</dd>
      <dt class="col-sm-3 text-muted">Recipients</dt><dd class="col-sm-9">{{ $batch->total_count }}</dd>
      <dt class="col-sm-3 text-muted">Message</dt><dd class="col-sm-9">{{ $batch->message_body }}</dd>
      @if ($batch->error_message)<dt class="col-sm-3 text-muted">Error</dt><dd class="col-sm-9 text-danger">{{ $batch->error_message }}</dd>@endif
    </dl>
  </div></div>

  <div class="card"><div class="card-header">Delivery log ({{ $batch->logs->count() }})</div><div class="card-body">
    @if ($batch->logs->isEmpty())
      <p class="text-muted mb-0">No per-recipient logs.</p>
    @else
      <table class="table align-middle w-100 js-dt">
        <thead><tr><th>Phone</th><th>Segments</th><th>Status</th><th>Error</th></tr></thead>
        <tbody>
          @foreach ($batch->logs as $log)
            <tr>
              <td>{{ $log->recipient_phone }}</td>
              <td>{{ $log->segment_count }}</td>
              <td>
                @php $lm = ['sent'=>'success','failed'=>'danger','queued'=>'secondary']; @endphp
                <span class="badge text-bg-{{ $lm[$log->status] ?? 'secondary' }}">{{ ucfirst($log->status) }}</span>
              </td>
              <td class="small text-danger">{{ $log->error_message ?? '' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div></div>
@endsection
