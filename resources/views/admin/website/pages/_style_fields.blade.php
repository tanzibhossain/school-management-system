{{-- Universal per-block "Style" tab — same fields for every block type.
     Vars: $prefix, $style --}}
@php $s = $style ?? []; @endphp
<div class="row g-2">
  <div class="col-6">
    <label class="form-label small text-muted mb-1">{{ __('Padding top (px)') }}</label>
    <input type="number" min="0" max="400" name="{{ $prefix }}[style][padding_top]" value="{{ $s['padding_top'] ?? '' }}" class="form-control form-control-sm" placeholder="{{ __('Default') }}">
  </div>
  <div class="col-6">
    <label class="form-label small text-muted mb-1">{{ __('Padding bottom (px)') }}</label>
    <input type="number" min="0" max="400" name="{{ $prefix }}[style][padding_bottom]" value="{{ $s['padding_bottom'] ?? '' }}" class="form-control form-control-sm" placeholder="{{ __('Default') }}">
  </div>
  <div class="col-6">
    <label class="form-label small text-muted mb-1">{{ __('Margin top (px)') }}</label>
    <input type="number" min="0" max="400" name="{{ $prefix }}[style][margin_top]" value="{{ $s['margin_top'] ?? '' }}" class="form-control form-control-sm" placeholder="0">
  </div>
  <div class="col-6">
    <label class="form-label small text-muted mb-1">{{ __('Margin bottom (px)') }}</label>
    <input type="number" min="0" max="400" name="{{ $prefix }}[style][margin_bottom]" value="{{ $s['margin_bottom'] ?? '' }}" class="form-control form-control-sm" placeholder="0">
  </div>

  <div class="col-6">
    <label class="form-label small text-muted mb-1">{{ __('Background color') }}</label>
    <div class="input-group input-group-sm js-color-pair">
      <input type="color" class="form-control form-control-color js-color-swatch" value="{{ ($s['bg_color'] ?? null) ?: '#ffffff' }}">
      <input type="text" name="{{ $prefix }}[style][bg_color]" value="{{ $s['bg_color'] ?? '' }}" class="form-control js-color-text" placeholder="{{ __('None') }}" maxlength="9">
    </div>
  </div>
  <div class="col-6">
    <label class="form-label small text-muted mb-1">{{ __('Text color') }}</label>
    <div class="input-group input-group-sm js-color-pair">
      <input type="color" class="form-control form-control-color js-color-swatch" value="{{ ($s['text_color'] ?? null) ?: '#000000' }}">
      <input type="text" name="{{ $prefix }}[style][text_color]" value="{{ $s['text_color'] ?? '' }}" class="form-control js-color-text" placeholder="{{ __('None') }}" maxlength="9">
    </div>
  </div>

  <div class="col-12">
    <label class="form-label small text-muted mb-1">{{ __('Background image URL') }}</label>
    <input type="text" name="{{ $prefix }}[style][bg_image]" value="{{ $s['bg_image'] ?? '' }}" class="form-control form-control-sm" placeholder="https://…">
  </div>
  <div class="col-12">
    <label class="form-label small text-muted mb-1 d-flex justify-content-between">
      <span>{{ __('Overlay darkness') }}</span>
      <span class="text-muted">{{ $s['bg_overlay'] ?? 0 }}%</span>
    </label>
    <input type="range" min="0" max="100" name="{{ $prefix }}[style][bg_overlay]" value="{{ $s['bg_overlay'] ?? 0 }}" class="form-range js-range-echo">
  </div>

  <div class="col-6">
    <label class="form-label small text-muted mb-1">{{ __('Corner radius (px)') }}</label>
    <input type="number" min="0" max="400" name="{{ $prefix }}[style][radius]" value="{{ $s['radius'] ?? '' }}" class="form-control form-control-sm" placeholder="0">
  </div>
  <div class="col-6">
    <label class="form-label small text-muted mb-1">{{ __('Shadow') }}</label>
    <select name="{{ $prefix }}[style][shadow]" class="form-select form-select-sm">
      <option value="" @selected(empty($s['shadow']))>{{ __('None') }}</option>
      <option value="sm" @selected(($s['shadow'] ?? '') === 'sm')>{{ __('Small') }}</option>
      <option value="md" @selected(($s['shadow'] ?? '') === 'md')>{{ __('Medium') }}</option>
      <option value="lg" @selected(($s['shadow'] ?? '') === 'lg')>{{ __('Large') }}</option>
    </select>
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
