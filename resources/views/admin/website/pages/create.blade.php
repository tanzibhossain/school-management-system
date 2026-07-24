@extends('layouts.admin')
@section('title', __('New Page'))
@section('content')
  @include('admin.partials.page-header', ['title' => __('New page'), 'crumbs' => [__('Website'), __('Pages'), __('New')]])

  <div class="row"><div class="col-lg-6">
    <div class="card"><div class="card-body">
      <form method="POST" action="{{ route('admin.pages.store') }}">
        @csrf
        <div class="mb-3"><label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label>
          <input name="title" class="form-control" value="{{ old('title') }}" required></div>
        <div class="mb-3"><label class="form-label">{{ __('Slug') }}</label>
          <div class="input-group"><span class="input-group-text">/</span>
            <input name="slug" class="form-control" value="{{ old('slug') }}" placeholder="{{ __('Auto From Title') }}"></div>
          <div class="form-text">{{ __('The Page URL. Leave Blank To Generate From The Title.') }}</div></div>
        <div class="mb-3"><label class="form-label">{{ __('Template') }} <span class="text-danger">*</span></label>
          <select name="template" class="form-select" required>
            <option value="full">{{ __('Full Width (No Sidebar)') }}</option>
            <option value="sidebar">{{ __('With Sidebar') }}</option>
          </select>
          <div class="form-text">{{ __('Ignored when starting from a saved template below — it already has its own.') }}</div>
        </div>
        @if ($templates->isNotEmpty())
          <div class="mb-3"><label class="form-label">{{ __('Start From') }}</label>
            <select name="page_template_id" class="form-select">
              <option value="">{{ __('Blank Page') }}</option>
              @if ($templates->whereNull('school_id')->isNotEmpty())
                <optgroup label="{{ __('Starter Templates') }}">
                  @foreach ($templates->whereNull('school_id') as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                  @endforeach
                </optgroup>
              @endif
              @if ($templates->whereNotNull('school_id')->isNotEmpty())
                <optgroup label="{{ __('My Templates') }}">
                  @foreach ($templates->whereNotNull('school_id') as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                  @endforeach
                </optgroup>
              @endif
            </select>
          </div>
        @endif
        <button class="btn btn-primary">Create &amp; edit</button>
        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
      </form>
    </div></div>
  </div></div>
@endsection
