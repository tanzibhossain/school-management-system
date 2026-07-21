@extends('layouts.admin')
@section('title', __('Modules'))
@section('content')
  @include('admin.partials.page-header', ['title' => 'Optional modules', 'crumbs' => ['Setup', 'Modules']])

  <form method="POST" action="{{ route('admin.modules.update') }}">
    @csrf @method('PUT')
    <div class="card"><div class="card-body">
      <p class="text-muted">Enable the optional modules your school uses. Disabled modules are hidden from the
        menu and their APIs return 403.</p>
      <div class="vstack gap-2">
        @foreach ($settings as $s)
          @php [$label, $desc] = $meta[$s['module']] ?? [ucfirst($s['module']), '']; @endphp
          <div class="d-flex align-items-center justify-content-between border rounded p-3">
            <div>
              <div class="fw-semibold">{{ $label }}</div>
              <div class="text-muted small">{{ $desc }}</div>
            </div>
            <div class="form-check form-switch fs-5 mb-0">
              <input class="form-check-input" type="checkbox" role="switch" name="enabled[]" value="{{ $s['module'] }}" id="mod-{{ $s['module'] }}" @checked($s['is_enabled'])>
              <label class="form-check-label visually-hidden" for="mod-{{ $s['module'] }}">{{ $label }}</label>
            </div>
          </div>
        @endforeach
      </div>
    </div></div>
    <div class="mt-4"><button class="btn btn-primary"><i class="bi bi-save"></i> {{ __('Save modules') }}</button></div>
  </form>
@endsection
