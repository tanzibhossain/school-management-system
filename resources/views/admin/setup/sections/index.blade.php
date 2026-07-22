@extends('layouts.admin')
@section('title', 'Sections · ' . $class->name)
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Sections — ' . $class->name,
    'crumbs' => [__('Setup'), __('Classes'), $class->name, __('Sections')],
    'action' => ['label' => __('New section'), 'modal' => 'createModal'],
  ])

  <div class="mb-3"><a href="{{ route('admin.classes.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> {{ __('Back To Classes') }}</a></div>

  <div class="card"><div class="card-body">
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Section') }}</th><th>{{ __('Capacity') }}</th><th>{{ __('Class Teacher') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($sections as $s)
          <tr>
            <td class="fw-semibold">{{ $s->name }}</td>
            <td>{{ $s->capacity ?? '—' }}</td>
            <td>{{ $s->classTeacher->name ?? '—' }}</td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $s->id }}">{{ __('Edit') }}</button>
              <form method="POST" action="{{ route('admin.classes.sections.destroy', [$class->id, $s->id]) }}" class="d-inline" onsubmit="return confirm('Delete section {{ $s->name }}?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">{{ __('Delete') }}</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @php
    $teacherOptions = function ($selected = null) use ($teachers) {
      $out = '<option value="">— none —</option>';
      foreach ($teachers as $t) {
        $sel = (int) $selected === (int) $t->id ? ' selected' : '';
        $out .= '<option value="' . $t->id . '"' . $sel . '>' . e($t->name) . '</option>';
      }
      return $out;
    };
  @endphp

  {{-- Create --}}
  <div class="modal fade" id="createModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="{{ route('admin.classes.sections.store', $class->id) }}">
      @csrf
      <div class="modal-header"><h5 class="modal-title">{{ __('New Section') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2"><label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
          <input name="name" class="form-control" value="{{ old('name') }}" placeholder="{{ __('E.g. A') }}" required></div>
        <div class="mb-2"><label class="form-label">{{ __('Capacity') }}</label>
          <input name="capacity" type="number" min="1" class="form-control" value="{{ old('capacity') }}"></div>
        <div><label class="form-label">{{ __('Class Teacher') }}</label>
          <select name="class_teacher_id" class="form-select js-select">{!! $teacherOptions(old('class_teacher_id')) !!}</select></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
    </form>
  </div></div></div>

  {{-- Edit --}}
  @foreach ($sections as $s)
    <div class="modal fade" id="editModal{{ $s->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.classes.sections.update', [$class->id, $s->id]) }}">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">{{ __('Edit Section') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <div class="mb-2"><label class="form-label">{{ __('Name') }} <span class="text-danger">*</span></label>
            <input name="name" class="form-control" value="{{ $s->name }}" required></div>
          <div class="mb-2"><label class="form-label">{{ __('Capacity') }}</label>
            <input name="capacity" type="number" min="1" class="form-control" value="{{ $s->capacity }}"></div>
          <div><label class="form-label">{{ __('Class Teacher') }}</label>
            <select name="class_teacher_id" class="form-select">{!! $teacherOptions($s->class_teacher_id) !!}</select></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
      </form>
    </div></div></div>
  @endforeach
@endsection
