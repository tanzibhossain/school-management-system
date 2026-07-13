@extends('layouts.admin')
@section('title', 'Website appearance')
@section('content')
  @include('admin.partials.page-header', [
    'title'  => 'Website appearance',
    'crumbs' => ['Setup', 'Appearance'],
  ])

  <form method="POST" action="{{ route('admin.appearance.update') }}">
    @csrf @method('PUT')
    <div class="row g-3">
      <div class="col-lg-6">
        <div class="card"><div class="card-header">Brand</div><div class="card-body">
          <div class="mb-3"><label class="form-label">Site name</label>
            <input name="site_name" class="form-control" value="{{ old('site_name', $settings->site_name) }}" placeholder="{{ $settings->site_name ?: 'Shown in header, tab title, footer' }}"></div>
          <div class="row g-3">
            <div class="col-sm-4"><label class="form-label">Primary color</label>
              <input type="color" name="primary_color" class="form-control form-control-color w-100" value="{{ old('primary_color', $settings->primary_color ?: '#1d4ed8') }}"></div>
            <div class="col-sm-4"><label class="form-label">Accent color</label>
              <input type="color" name="accent_color" class="form-control form-control-color w-100" value="{{ old('accent_color', $settings->accent_color ?: '#f59e0b') }}"></div>
            <div class="col-sm-4"><label class="form-label">Heading color</label>
              <input type="color" name="heading_color" class="form-control form-control-color w-100" value="{{ old('heading_color', $settings->heading_color ?: '#0f172a') }}"></div>
          </div>
          <div class="form-text mt-2">Primary color is used for the top header bar, brand text, and buttons.</div>
        </div></div>
      </div>

      <div class="col-lg-6">
        <div class="card"><div class="card-header">Top header bar</div><div class="card-body">
          <div class="mb-3"><label class="form-label">Welcome text</label>
            <input name="topbar_welcome" class="form-control" value="{{ old('topbar_welcome', $settings->topbar_welcome) }}" placeholder="e.g. Welcome to our school"></div>
          <div class="mb-3"><label class="form-label">Phone</label>
            <input name="topbar_phone" class="form-control" value="{{ old('topbar_phone', $settings->topbar_phone) }}" placeholder="e.g. 01309115394, 01710866871"></div>
          <div class="mb-0"><label class="form-label">Text color</label>
            <input type="color" name="topbar_text_color" class="form-control form-control-color" value="{{ old('topbar_text_color', $settings->topbar_text_color ?: '#ffffff') }}">
            <div class="form-text">Color of the welcome text, date, and phone on the top bar (background uses the primary color).</div></div>
        </div></div>
      </div>
    </div>

    <div class="mt-3"><button class="btn btn-primary">Save appearance</button>
      <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-secondary">View site <i class="bi bi-box-arrow-up-right"></i></a></div>
  </form>
@endsection
