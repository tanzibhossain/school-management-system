@extends('layouts.admin')
@section('title', __('Admit Cards'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => __('Admit cards'),
    'crumbs' => [__('Certificates'), __('Admit cards')],
    'action' => ['label' => __('Generate'), 'modal' => 'genModal'],
  ])
  @include('admin.certificates._tabs', ['active' => 'admit-cards'])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Student') }}</th><th>{{ __('Exam') }}</th><th>{{ __('Generated') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($cards as $c)
          <tr>
            <td class="fw-semibold">{{ $c->student?->name ?? '—' }} <span class="text-muted small">({{ $c->student?->student_id }})</span></td>
            <td>{{ $c->exam?->title ?? '—' }}</td>
            <td class="small">{{ optional($c->generated_at)->format('d M Y H:i') }}</td>
            <td class="text-end"><a class="btn btn-sm btn-outline-danger" href="{{ route('admin.admit-cards.download', $c->id) }}" target="_blank"><i class="bi bi-file-pdf"></i> {{ __('PDF') }}</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="genModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.admit-cards.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('Generate Admit Card') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">{{ __('Student') }} <span class="text-danger">*</span></label>
          <select name="student_id" class="form-select js-select" required>
            <option value="">— select —</option>
            @foreach ($students as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ $s->student_id }})</option>@endforeach
          </select></div>
        <div class="col-12"><label class="form-label">{{ __('Exam') }} <span class="text-danger">*</span></label>
          <select name="exam_id" class="form-select" required>
            <option value="">— select —</option>
            @foreach ($exams as $e)<option value="{{ $e->id }}">{{ $e->title }}</option>@endforeach
          </select></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Generate') }}</button></div>
    </form>
  </div></div></div>
@endsection
