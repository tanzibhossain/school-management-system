@extends('layouts.admin')
@section('title', __('Testimonials'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Testimonials',
    'crumbs' => ['Certificates', 'Testimonials'],
    'action' => ['label' => 'Issue testimonial', 'modal' => 'issueModal'],
  ])
  @include('admin.certificates._tabs', ['active' => 'testimonials'])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Number') }}</th><th>{{ __('Student') }}</th><th>{{ __('Issued') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($testimonials as $t)
          <tr>
            <td><code>{{ $t->testimonial_number }}</code></td>
            <td class="fw-semibold">{{ $t->student?->name ?? '—' }} <span class="text-muted small">({{ $t->student?->student_id }})</span></td>
            <td class="small">{{ optional($t->issued_date)->format('d M Y') }}</td>
            <td><span class="badge text-bg-{{ $t->status === 'issued' ? 'success' : 'secondary' }}">{{ ucfirst($t->status) }}</span></td>
            <td class="text-end"><a class="btn btn-sm btn-outline-danger" href="{{ route('admin.testimonials.download', $t->id) }}" target="_blank"><i class="bi bi-file-pdf"></i> {{ __('PDF') }}</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="issueModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.testimonials.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('Issue Testimonial') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-12"><label class="form-label">{{ __('Student') }} <span class="text-danger">*</span></label>
          <select name="student_id" class="form-select js-select" required>
            <option value="">— select —</option>
            @foreach ($students as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ $s->student_id }})</option>@endforeach
          </select></div>
        <div class="col-12"><label class="form-label">{{ __('Template') }}</label>
          <select name="template_id" class="form-select"><option value="">{{ __('Default') }}</option>
            @foreach ($templates as $tpl)<option value="{{ $tpl->id }}">{{ $tpl->name }}</option>@endforeach
          </select>
          @if ($templates->isEmpty())<div class="form-text text-danger">{{ __('No Templates — Add One Under The Templates Tab First.') }}</div>@endif
        </div>
        <div class="col-12"><label class="form-label">{{ __('Conduct Remark') }} <span class="text-danger">*</span></label>
          <textarea name="conduct_remark" rows="2" class="form-control" required></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Issue') }}</button></div>
    </form>
  </div></div></div>
@endsection
