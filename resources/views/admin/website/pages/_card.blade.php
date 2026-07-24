{{-- One block card: a compact rail row (icon + label + reorder/remove) that
     expands, one at a time within its list, into its Content/Style/Layout
     settings — the "click a layer, its settings open" pattern from
     docs/modules/28-elementor-block-editor-plan.md Milestone 3.
     Vars: $prefix, $type, $label, $data, $spec, $style, $layout, $gridTypes, $icon --}}
@php
    $style = $style ?? [];
    $layout = $layout ?? [];
    $gridTypes = $gridTypes ?? [];
    $isGrid = in_array($type, $gridTypes, true);
    $tabId = preg_replace('/[^a-zA-Z0-9]/', '-', $prefix);
    $icon = $icon ?? 'bi-square';
@endphp
<div class="card mb-2 block-card">
  {{-- aria-expanded/aria-controls mirror the show/hide toggleBlockCard() JS
       (edit.blade.php) already drives via the block-settings pane's
       display:none/block — kept in sync there, not duplicated in markup,
       since the pane always starts collapsed. --}}
  <div class="block-row d-flex align-items-center gap-2 px-2 py-2 js-block-toggle" role="button" tabindex="0"
       aria-expanded="false" aria-controls="tab-content-{{ $tabId }}" aria-label="{{ $label }}">
    {{-- Decorative — dragging the handle (or anywhere on the row) is now the
         ONLY way to reorder a block; there is no keyboard-operable
         equivalent since the Move Up/Down buttons were removed in favor of
         drag-only reordering (see docs/modules/28-elementor-block-editor-plan.md
         §7w for the accessibility trade-off this leaves open). --}}
    <i class="bi bi-grip-vertical text-muted js-drag-handle" aria-hidden="true" title="{{ __('Drag To Reorder') }}"></i>
    <i class="bi {{ $icon }} text-brand" aria-hidden="true"></i>
    <span class="small fw-semibold flex-grow-1 text-truncate">{{ $label }}</span>
    <span class="btn-group btn-group-sm">
      <button type="button" class="btn btn-outline-danger js-remove" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}: {{ $label }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
    </span>
    <i class="bi bi-chevron-down small text-muted js-block-chevron" aria-hidden="true"></i>
  </div>
  <div class="block-settings border-top" style="display:none;">
    <div class="card-body py-2 px-2">
      <ul class="nav nav-tabs mb-2" role="tablist">
        <li class="nav-item"><button type="button" class="nav-link active py-1 px-2 small" data-bs-toggle="tab" data-bs-target="#tab-content-{{ $tabId }}">{{ __('Content') }}</button></li>
        <li class="nav-item"><button type="button" class="nav-link py-1 px-2 small" data-bs-toggle="tab" data-bs-target="#tab-style-{{ $tabId }}"><i class="bi bi-palette"></i> {{ __('Style') }}</button></li>
        {{-- Internal id/name "layout" is unchanged (tab-layout-*, [layout][...]
             field names) — only the visible label changed to "Advanced" to
             match its now-broader scope (spacing/width/border/background/
             responsive), see docs/modules/28-elementor-block-editor-plan.md §7aa. --}}
        <li class="nav-item"><button type="button" class="nav-link py-1 px-2 small" data-bs-toggle="tab" data-bs-target="#tab-layout-{{ $tabId }}"><i class="bi bi-sliders"></i> {{ __('Advanced') }}</button></li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-content-{{ $tabId }}">
          @if ($type === 'admission_form')
            @include('admin.website.pages._admission_form_fields', ['prefix' => $prefix, 'data' => $data])
          @else
            @include('admin.website.pages._fields', ['prefix' => $prefix, 'type' => $type, 'data' => $data, 'spec' => $spec])
          @endif
          @if (in_array($type, ['container', 'grid'], true))
            {{-- Nested children mini-rail — recursive: a child can itself be
                 a container/grid, up to PageRenderService::MAX_NESTING_DEPTH
                 (see _nested_blocks.blade.php). See
                 docs/modules/28-elementor-block-editor-plan.md §7d/§7g. --}}
            @include('admin.website.pages._nested_blocks', ['prefix' => $prefix, 'children' => $data['blocks'] ?? [], 'spec' => $spec, 'gridTypes' => $gridTypes, 'blockIcons' => $blockIcons ?? []])
          @endif
        </div>
        <div class="tab-pane fade" id="tab-style-{{ $tabId }}">
          @include('admin.website.pages._style_fields', ['prefix' => $prefix, 'style' => $style])
        </div>
        <div class="tab-pane fade" id="tab-layout-{{ $tabId }}">
          @include('admin.website.pages._layout_fields', ['prefix' => $prefix, 'layout' => $layout, 'isGrid' => $isGrid, 'style' => $style])
        </div>
      </div>
    </div>
  </div>
</div>
