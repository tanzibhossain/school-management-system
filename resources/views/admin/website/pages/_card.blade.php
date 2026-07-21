{{-- One block card. Vars: $prefix, $type, $label, $data, $spec --}}
<div class="card mb-2 block-card">
  <div class="card-header py-1 px-2 d-flex justify-content-between align-items-center">
    <span class="small fw-semibold">{{ $label }}</span>
    <span class="btn-group btn-group-sm">
      <button type="button" class="btn btn-outline-secondary js-up" title="{{ __('Move up') }}"><i class="bi bi-arrow-up"></i></button>
      <button type="button" class="btn btn-outline-secondary js-down" title="{{ __('Move down') }}"><i class="bi bi-arrow-down"></i></button>
      <button type="button" class="btn btn-outline-danger js-remove" title="{{ __('Remove') }}"><i class="bi bi-trash"></i></button>
    </span>
  </div>
  <div class="card-body py-2 px-2">
    @if ($type === 'admission_form')
      @include('admin.website.pages._admission_form_fields', ['prefix' => $prefix, 'data' => $data])
    @else
      @include('admin.website.pages._fields', ['prefix' => $prefix, 'type' => $type, 'data' => $data, 'spec' => $spec])
    @endif
  </div>
</div>
