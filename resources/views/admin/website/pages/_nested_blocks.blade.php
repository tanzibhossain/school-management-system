{{-- A Container/Grid block's own nested children — a self-contained mini
     rail reusing this same _card.blade.php recursively (single-level
     nesting only: children are always leaf types, see
     PageRenderService::LEAF_BLOCKS, so this include never recurses again).
     Vars: $prefix (this container's own prefix, e.g. "blocks[2]"),
     $children, $spec, $gridTypes, $blockIcons

     Known limitation: session undo/redo captures/restores a card's fields
     positionally (see edit.blade.php's captureCardFields/applyCardFields),
     which also sweeps up nested children's fields since they're DOM
     descendants — but restoreList() rebuilds a container fresh from its
     empty <template> before reapplying values, so if a child was
     added/removed since the last history snapshot, the restore can
     mismatch. Same documented gap as admission_form's dynamic custom
     fields; not solved here for the same reason (would need fully
     recursive, structure-aware snapshots, not just positional field values). --}}
@php $leafBlocks = \App\Modules\Website\Services\PageRenderService::LEAF_BLOCKS; @endphp
<div class="nested-blocks-wrap mt-3">
  <hr class="my-2">
  <h6 class="small text-muted text-uppercase mb-2">{{ __('Children') }}</h6>
  <div class="nested-blocks-list mb-2" data-prefix="{{ $prefix }}">
    @foreach ($children as $i => $child)
      @include('admin.website.pages._card', [
        'prefix' => "{$prefix}[data][blocks][{$i}]",
        'type' => $child['type'],
        'label' => $leafBlocks[$child['type']] ?? $child['type'],
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
      @foreach ($leafBlocks as $t => $l)<option value="{{ $t }}">{{ $l }}</option>@endforeach
    </select>
    <button type="button" class="btn btn-outline-primary js-nested-add-btn"><i class="bi bi-plus-lg"></i> {{ __('Add') }}</button>
  </div>
</div>
