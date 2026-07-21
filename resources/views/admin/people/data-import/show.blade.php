@extends('layouts.admin')
@section('title', 'Import #' . $batch->id)
@section('content')
  @php $m = ['queued'=>'secondary','processing'=>'info','completed'=>'success','failed'=>'danger']; @endphp
  @include('admin.partials.page-header', ['title' => 'Import #' . $batch->id, 'crumbs' => ['People', 'Data import', 'Batch #' . $batch->id]])

  <div class="mb-3"><a href="{{ route('admin.data-import.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> {{ __('Back To Imports') }}</a></div>

  <div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Status') }}</div><div class="h5 mb-0"><span class="badge text-bg-{{ $m[$batch->status] ?? 'secondary' }}">{{ ucfirst($batch->status) }}</span></div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Total Rows') }}</div><div class="h5 mb-0">{{ $batch->total_rows }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Imported') }}</div><div class="h5 mb-0 text-success">{{ $batch->success_count }}</div></div></div></div>
    <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted small">{{ __('Skipped') }}</div><div class="h5 mb-0 text-warning">{{ $batch->skipped_count }}</div></div></div></div>
  </div>

  <div class="card"><div class="card-header">Row errors ({{ is_array($batch->errors) ? count($batch->errors) : 0 }})</div><div class="card-body">
    @if (empty($batch->errors))
      <p class="text-muted mb-0">No row errors.{{ $batch->error_message ? ' ' . $batch->error_message : '' }}</p>
    @else
      <table class="table align-middle mb-0">
        <thead><tr><th>{{ __('Row') }}</th><th>{{ __('Error') }}</th></tr></thead>
        <tbody>
          @foreach ($batch->errors as $err)
            <tr>
              <td>{{ is_array($err) ? ($err['row'] ?? '—') : '—' }}</td>
              <td class="small text-danger">{{ is_array($err) ? ($err['message'] ?? json_encode($err)) : $err }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div></div>
@endsection
