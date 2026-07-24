{{-- Universal per-block "Style" tab — same fields for every block type.
     Vars: $prefix, $style
     Everything that isn't purely typographic/color lives on the Advanced tab
     now (_layout_fields.blade.php) — see docs/modules/28-elementor-block-editor-plan.md
     §7x (padding/margin) and §7aa (background/border/radius/shadow/width).
     All still `[style][...]` keys underneath (unchanged data model), just
     rendered from a different tab's partial. This tab is left with the two
     fields that are genuinely about this block's own look, not its box
     model: text color and entrance animation. --}}
@php $s = $style ?? []; @endphp
<div class="d-flex justify-content-end gap-1 mb-2">
  <button type="button" class="btn btn-sm btn-outline-secondary js-copy-style" title="{{ __('Copy This Block\'s Style') }}"><i class="bi bi-clipboard"></i> {{ __('Copy Style') }}</button>
  <button type="button" class="btn btn-sm btn-outline-secondary js-paste-style" disabled title="{{ __('Paste The Copied Style Here') }}"><i class="bi bi-clipboard-check"></i> {{ __('Paste Style') }}</button>
</div>
<div class="row g-2">
  <div class="col-12">
    <label class="form-label small text-muted mb-1">{{ __('Text color') }}</label>
    <div class="input-group input-group-sm js-color-pair">
      <input type="color" class="form-control form-control-color js-color-swatch" value="{{ ($s['text_color'] ?? null) ?: '#000000' }}">
      <input type="text" name="{{ $prefix }}[style][text_color]" value="{{ $s['text_color'] ?? '' }}" class="form-control js-color-text" placeholder="{{ __('None') }}" maxlength="9">
    </div>
  </div>

  <div class="col-12">
    <label class="form-label small text-muted mb-1">{{ __('Entrance animation') }}</label>
    <select name="{{ $prefix }}[style][animation]" class="form-select form-select-sm">
      <option value="" @selected(empty($s['animation']))>{{ __('None') }}</option>
      <option value="fade" @selected(($s['animation'] ?? '') === 'fade')>{{ __('Fade in') }}</option>
      <option value="up" @selected(($s['animation'] ?? '') === 'up')>{{ __('Slide up') }}</option>
    </select>
  </div>
</div>
