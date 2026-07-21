@extends('layouts.admin')
@section('title', 'ID card batch #' . $batch->id)
@section('content')
  @php $m = ['queued'=>'secondary','processing'=>'info','completed'=>'success','failed'=>'danger']; @endphp
  @include('admin.partials.page-header', ['title' => 'ID card batch #' . $batch->id, 'crumbs' => ['Certificates', 'ID cards', 'Batch #' . $batch->id]])

  <div class="mb-3"><a href="{{ route('admin.id-cards.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> {{ __('Back to batches') }}</a></div>

  <div class="card mb-4"><div class="card-body">
    <dl class="row mb-0">
      <dt class="col-sm-3 text-muted">{{ __('Status') }}</dt><dd class="col-sm-9"><span class="badge text-bg-{{ $m[$batch->status] ?? 'secondary' }}">{{ ucfirst($batch->status) }}</span></dd>
      <dt class="col-sm-3 text-muted">{{ __('Type') }}</dt><dd class="col-sm-9 text-capitalize">{{ $batch->type }}</dd>
      <dt class="col-sm-3 text-muted">{{ __('Template') }}</dt><dd class="col-sm-9">{{ $batch->template?->name ?? '—' }}</dd>
      <dt class="col-sm-3 text-muted">{{ __('Cards') }}</dt><dd class="col-sm-9">{{ $batch->total_count }}</dd>
      @if ($batch->error_message)<dt class="col-sm-3 text-muted">{{ __('Error') }}</dt><dd class="col-sm-9 text-danger">{{ $batch->error_message }}</dd>@endif
    </dl>
  </div></div>

  <div class="card"><div class="card-header">Generated files ({{ $batch->files->count() }})</div><div class="card-body">
    @if ($batch->files->isEmpty())
      <p class="text-muted mb-0">No files{{ $batch->status === 'queued' ? ' — still processing.' : '.' }}</p>
    @else
      <table class="table align-middle mb-0">
        <thead><tr><th>{{ __('File') }}</th><th>{{ __('Cards') }}</th><th class="text-end" data-orderable="false"></th></tr></thead>
        <tbody>
          @foreach ($batch->files as $f)
            <tr>
              <td>Sheet {{ $f->file_index + 1 }}</td>
              <td>{{ $f->card_count }}</td>
              <td class="text-end"><a class="btn btn-sm btn-outline-danger" href="{{ route('admin.id-cards.download', [$batch->id, $f->id]) }}" target="_blank"><i class="bi bi-file-pdf"></i> {{ __('PDF') }}</a></td>
            </tr>
          @endforeach
        </tbody>
      </table>
    @endif
  </div></div>
@endsection
