@extends('layouts.admin')
@section('title', __('Exams'))
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Exams',
    'crumbs' => ['Academics', 'Exams'],
    'action' => ['label' => 'New exam', 'modal' => 'createModal'],
  ])

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Title') }}</th><th>{{ __('Type') }}</th><th>{{ __('Class') }}</th><th>{{ __('Dates') }}</th><th>{{ __('Subjects') }}</th><th>{{ __('Status') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($exams as $e)
          <tr>
            <td class="fw-semibold">{{ $e->title }}</td>
            <td>{{ $e->examType?->name ?? '—' }}</td>
            <td>{{ $e->schoolClass?->name ?? '—' }}</td>
            <td class="small">{{ optional($e->start_date)->format('d M') }} – {{ optional($e->end_date)->format('d M Y') }}</td>
            <td><span class="badge text-bg-light border text-muted">{{ $e->subjects_count }}</span></td>
            <td>
              @php $m = ['draft'=>'secondary','published'=>'primary','completed'=>'success']; @endphp
              <span class="badge text-bg-{{ $m[$e->status] ?? 'secondary' }}">{{ ucfirst($e->status) }}</span>
            </td>
            <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.exams.show', $e->id) }}">{{ __('Open') }}</a></td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  <div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" action="{{ route('admin.exams.store') }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('New exam') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body row g-3">
        <div class="col-md-8"><label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label>
          <input name="title" class="form-control" value="{{ old('title') }}" placeholder="{{ __('e.g. Midterm 2026') }}" required></div>
        <div class="col-md-4"><label class="form-label">{{ __('Type') }} <span class="text-danger">*</span></label>
          <select name="exam_type_id" class="form-select" required>
            <option value="">— select —</option>
            @foreach ($types as $t)<option value="{{ $t->id }}" @selected(old('exam_type_id')==$t->id)>{{ $t->name }}</option>@endforeach
          </select></div>
        <div class="col-md-4"><label class="form-label">{{ __('Academic year') }} <span class="text-danger">*</span></label>
          <select name="academic_year_id" class="form-select" required>
            @foreach ($years as $y)<option value="{{ $y->id }}" @selected($y->is_current)>{{ $y->year }}</option>@endforeach
          </select></div>
        <div class="col-md-4"><label class="form-label">{{ __('Class') }} <span class="text-danger">*</span></label>
          <select name="class_id" class="form-select" required>
            <option value="">— select —</option>
            @foreach ($classes as $c)<option value="{{ $c->id }}" @selected(old('class_id')==$c->id)>{{ $c->name }}</option>@endforeach
          </select></div>
        <div class="col-md-4"><label class="form-label">{{ __('Section') }} <span class="text-muted small">(optional)</span></label>
          <select name="section_id" class="form-select"><option value="">{{ __('All sections') }}</option>
            @foreach ($sections as $s)<option value="{{ $s->id }}" data-class="{{ $s->class_id }}">{{ $s->name }}</option>@endforeach
          </select></div>
        <div class="col-md-6"><label class="form-label">{{ __('Start date') }} <span class="text-danger">*</span></label>
          <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required></div>
        <div class="col-md-6"><label class="form-label">{{ __('End date') }} <span class="text-danger">*</span></label>
          <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Create') }}</button></div>
    </form>
  </div></div></div>
@endsection
