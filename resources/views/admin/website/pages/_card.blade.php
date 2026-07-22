{{-- One block card. Vars: $prefix, $type, $label, $data, $spec, $style, $layout, $gridTypes --}}
@php
    $style = $style ?? [];
    $layout = $layout ?? [];
    $gridTypes = $gridTypes ?? [];
    $isGrid = in_array($type, $gridTypes, true);
    $tabId = preg_replace('/[^a-zA-Z0-9]/', '-', $prefix);
@endphp
<div class="card mb-2 block-card">
  <div class="card-header py-1 px-2 d-flex justify-content-between align-items-center">
    <span class="small fw-semibold">{{ $label }}</span>
    <span class="btn-group btn-group-sm">
      <button type="button" class="btn btn-outline-secondary js-up" title="{{ __('Move Up') }}"><i class="bi bi-arrow-up"></i></button>
      <button type="button" class="btn btn-outline-secondary js-down" title="{{ __('Move Down') }}"><i class="bi bi-arrow-down"></i></button>
      <button type="button" class="btn btn-outline-danger js-remove" title="{{ __('Remove') }}"><i class="bi bi-trash"></i></button>
    </span>
  </div>
  <div class="card-body py-2 px-2">
    <ul class="nav nav-tabs mb-2" role="tablist">
      <li class="nav-item"><button type="button" class="nav-link active py-1 px-2 small" data-bs-toggle="tab" data-bs-target="#tab-content-{{ $tabId }}">{{ __('Content') }}</button></li>
      <li class="nav-item"><button type="button" class="nav-link py-1 px-2 small" data-bs-toggle="tab" data-bs-target="#tab-style-{{ $tabId }}"><i class="bi bi-palette"></i> {{ __('Style') }}</button></li>
      <li class="nav-item"><button type="button" class="nav-link py-1 px-2 small" data-bs-toggle="tab" data-bs-target="#tab-layout-{{ $tabId }}"><i class="bi bi-layout-three-columns"></i> {{ __('Layout') }}</button></li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="tab-content-{{ $tabId }}">
        @if ($type === 'admission_form')
          @include('admin.website.pages._admission_form_fields', ['prefix' => $prefix, 'data' => $data])
        @else
          @include('admin.website.pages._fields', ['prefix' => $prefix, 'type' => $type, 'data' => $data, 'spec' => $spec])
        @endif
      </div>
      <div class="tab-pane fade" id="tab-style-{{ $tabId }}">
        @include('admin.website.pages._style_fields', ['prefix' => $prefix, 'style' => $style])
      </div>
      <div class="tab-pane fade" id="tab-layout-{{ $tabId }}">
        @include('admin.website.pages._layout_fields', ['prefix' => $prefix, 'layout' => $layout, 'isGrid' => $isGrid])
      </div>
    </div>
  </div>
</div>
