@extends('layouts.admin')
@section('title', 'New page')
@section('content')
  @include('admin.partials.page-header', ['title' => 'New page', 'crumbs' => ['Website', 'Pages', 'New']])

  <div class="row"><div class="col-lg-6">
    <div class="card"><div class="card-body">
      <form method="POST" action="{{ route('admin.pages.store') }}">
        @csrf
        <div class="mb-3"><label class="form-label">Title <span class="text-danger">*</span></label>
          <input name="title" class="form-control" value="{{ old('title') }}" required></div>
        <div class="mb-3"><label class="form-label">Slug</label>
          <div class="input-group"><span class="input-group-text">/</span>
            <input name="slug" class="form-control" value="{{ old('slug') }}" placeholder="auto from title"></div>
          <div class="form-text">The page URL. Leave blank to generate from the title.</div></div>
        <div class="mb-3"><label class="form-label">Template <span class="text-danger">*</span></label>
          <select name="template" class="form-select" required>
            <option value="full">Full width (no sidebar)</option>
            <option value="sidebar">With sidebar</option>
          </select></div>
        <button class="btn btn-primary">Create &amp; edit</button>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">Cancel</a>
      </form>
    </div></div>
  </div></div>
@endsection
