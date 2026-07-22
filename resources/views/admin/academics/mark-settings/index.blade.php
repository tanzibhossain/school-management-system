@extends('layouts.admin')
@section('title', __('Mark Settings'))
@section('content')
  @include('admin.partials.page-header', ['title' => __('Mark settings'), 'crumbs' => [__('Academics'), __('Mark settings')]])

  <div class="card"><div class="card-body">
    <p class="text-muted">{{ __('Per-class Grading Configuration. Grade Boundaries Must Be Applied Before Results Can Be Calculated.') }}</p>
    <table class="table table-hover align-middle w-100 js-dt">
      <thead><tr><th>{{ __('Class') }}</th><th>{{ __('Mode') }}</th><th>{{ __('Strategy') }}</th><th>{{ __('Grade Boundaries') }}</th><th class="text-end" data-orderable="false">{{ __('Actions') }}</th></tr></thead>
      <tbody>
        @foreach ($classes as $c)
          @php $s = $settings[$c->id] ?? null; $bc = $boundaryCounts[$c->id] ?? 0; @endphp
          <tr>
            <td class="fw-semibold">{{ $c->name }}</td>
            <td class="text-capitalize">{{ $s->mode ?? 'mark' }}</td>
            <td>{{ $s->result_strategy ?? 'bd_national' }}</td>
            <td>
              @if ($bc > 0)<span class="badge text-bg-success">{{ $bc }} set</span>@else<span class="badge text-bg-warning">{{ __('None') }}</span>@endif
            </td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal{{ $c->id }}">{{ __('Settings') }}</button>
              <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#gradeModal{{ $c->id }}">{{ __('Grade Template') }}</button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div></div>

  @foreach ($classes as $c)
    @php $s = $settings[$c->id] ?? null; @endphp
    {{-- Settings --}}
    <div class="modal fade" id="editModal{{ $c->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.mark-settings.update', $c->id) }}">
        @csrf @method('PUT')
        <div class="modal-header"><h5 class="modal-title">{{ $c->name }} — settings</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body row g-3">
          <div class="col-md-6"><label class="form-label">{{ __('Mode') }}</label>
            <select name="mode" class="form-select">
              <option value="mark" @selected(($s->mode ?? 'mark')==='mark')>{{ __('Mark') }}</option>
              <option value="grade" @selected(($s->mode ?? '')==='grade')>{{ __('Grade') }}</option>
            </select></div>
          <div class="col-md-6"><label class="form-label">{{ __('Result Strategy') }}</label>
            <select name="result_strategy" class="form-select">
              @foreach (['bd_national','simple_average','weighted_average','percentage_only'] as $rs)
                <option value="{{ $rs }}" @selected(($s->result_strategy ?? 'bd_national')===$rs)>{{ $rs }}</option>
              @endforeach
            </select></div>
          <div class="col-md-6"><label class="form-label">{{ __('Grace Marks Cap') }}</label>
            <input type="number" step="0.01" min="0" name="grace_marks_cap" class="form-control" value="{{ $s->grace_marks_cap ?? 0 }}"></div>
          <div class="col-md-6 d-flex align-items-end">
            <div class="form-check"><input type="hidden" name="show_merit_position" value="0"><input class="form-check-input" type="checkbox" name="show_merit_position" value="1" id="merit{{ $c->id }}" @checked($s->show_merit_position ?? false)><label class="form-check-label" for="merit{{ $c->id }}">{{ __('Show Merit Position') }}</label></div>
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Save') }}</button></div>
      </form>
    </div></div></div>

    {{-- Grade template --}}
    <div class="modal fade" id="gradeModal{{ $c->id }}" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
      <form method="POST" action="{{ route('admin.mark-settings.apply-template', $c->id) }}">
        @csrf
        <div class="modal-header"><h5 class="modal-title">{{ $c->name }} — grade template</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
          <label class="form-label">{{ __('Template') }}</label>
          <select name="template" class="form-select">
            @foreach ($templates as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
          </select>
          <div class="form-text text-danger">Applying replaces this class's existing grade boundaries.</div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button><button class="btn btn-primary">{{ __('Apply') }}</button></div>
      </form>
    </div></div></div>
  @endforeach
@endsection
