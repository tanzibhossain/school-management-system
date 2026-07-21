@extends('layouts.admin')
@section('title', __('New page'))
@section('content')
  @include('admin.partials.page-header', ['title' => 'New page', 'crumbs' => ['Website', 'Pages', 'New']])

  <div class="row"><div class="col-lg-6">
    <div class="card"><div class="card-body">
      <form method="POST" action="{{ route('admin.pages.store') }}">
        @csrf
        <div class="mb-3"><label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label>
          <input name="title" class="form-control" value="{{ old('title') }}" required></div>
        <div class="mb-3"><label class="form-label">{{ __('Slug') }}</label>
          <div class="input-group"><span class="input-group-text">/</span>
            <input name="slug" class="form-control" value="{{ old('slug') }}" placeholder="{{ __('auto from title') }}"></div>
          <div class="form-text">{{ __('The page URL. Leave blank to generate from the title.') }}</div></div>
        <div class="mb-3"><label class="form-label">{{ __('Template') }} <span class="text-danger">*</span></label>
          <select name="template" class="form-select" required>
            <option value="full">{{ __('Full width (no sidebar)') }}</option>
            <option value="sidebar">{{ __('With sidebar') }}</option>
          </select></div>
        <button class="btn btn-primary">Create &amp; edit</button>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
      </form>
    </div></div>
  </div></div>
@endsection
