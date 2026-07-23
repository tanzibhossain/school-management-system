{{-- A Container/Grid block's own nested children — a self-contained mini
     rail reusing this same _card.blade.php recursively. Genuinely
     recursive now (see §7g in docs/modules/28-elementor-block-editor-plan.md):
     a child CAN itself be a container/grid, in which case including
     _card.blade.php for it hits its own `@if (container/grid)` branch and
     includes THIS partial again, one level deeper — up to
     PageRenderService::MAX_NESTING_DEPTH, enforced here by simply not
     offering Container/Grid as an addable child type once that depth is
     reached (the backend's normalizeBlocks()/cleanBlocks() enforce the
     same cap independently on save, so this is UX politeness, not the
     real guard).
     Vars: $prefix (this container's own prefix, e.g. "blocks[2]" at the
     top level, or "blocks[2][data][blocks][0]" one level deeper, …),
     $children, $spec, $gridTypes, $blockIcons

     Session undo/redo captures/restores a container's children fully
     recursively and structure-aware (edit.blade.php's captureCard()/
     restoreCardInto()) — each card's OWN fields are isolated from its
     children's, and children are rebuilt as a real tree rather than a flat
     positional list, so a child added/removed since the last history
     snapshot round-trips correctly. admission_form's dynamic custom fields
     remain the one still-positional exception (a different, unrelated data
     shape — not a container/grid). --}}
@php
  // How many container/grid levels deep THIS container itself already sits
  // at — 0 for a top-level block. Every extra nesting level adds one more
  // "[data][blocks]" segment to the prefix, so counting them is exact.
  $ownDepth = substr_count($prefix, '[data][blocks]');
  $maxDepth = \App\Modules\Website\Services\PageRenderService::MAX_NESTING_DEPTH;
  $allBlocks = \App\Modules\Website\Services\PageRenderService::BLOCKS;
  // Types offered for a NEW child here — children sit at $ownDepth + 1.
  $addableTypes = ($ownDepth + 1) >= $maxDepth
    ? \App\Modules\Website\Services\PageRenderService::LEAF_BLOCKS
    : $allBlocks;
@endphp
<div class="nested-blocks-wrap mt-3">
  <hr class="my-2">
  <h6 class="small text-muted text-uppercase mb-2">{{ __('Children') }}</h6>
  <div class="nested-blocks-list mb-2" data-prefix="{{ $prefix }}">
    @foreach ($children as $i => $child)
      @include('admin.website.pages._card', [
        'prefix' => "{$prefix}[data][blocks][{$i}]",
        'type' => $child['type'],
        'label' => $allBlocks[$child['type']] ?? $child['type'],
        'data' => $child['data'] ?? [],
        'spec' => $spec,
        'style' => $child['style'] ?? [],
        'layout' => $child['layout'] ?? [],
        'gridTypes' => $gridTypes,
        'icon' => $blockIcons[$child['type']] ?? 'bi-square',
        'blockIcons' => $blockIcons,
      ])
    @endforeach
  </div>
  <p class="text-muted small mb-2 js-nested-empty" @if(count($children)) style="display:none" @endif>{{ __('No Blocks Yet — Add One Below.') }}</p>
  <div class="input-group input-group-sm">
    <select class="form-select js-nested-type">
      @foreach ($addableTypes as $t => $l)<option value="{{ $t }}">{{ $l }}</option>@endforeach
    </select>
    <button type="button" class="btn btn-outline-primary js-nested-add-btn"><i class="bi bi-plus-lg"></i> {{ __('Add') }}</button>
  </div>
</div>
