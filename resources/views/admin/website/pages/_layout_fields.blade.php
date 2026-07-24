{{-- Universal per-block "Advanced" tab (was "Layout" — renamed in
     _card.blade.php's nav-link label only, internal tab-layout-* IDs are
     unchanged for backward JS/CSS compat). Four independently-collapsible
     sections (Layout/Border/Background/Responsive, none exclusive — opening
     one does not close another, matching how this control is commonly laid
     out elsewhere), plus the always-visible grid-columns control for
     Container/Grid blocks at the top, outside the accordion.
     Vars: $prefix, $layout, $style, $isGrid
     See docs/modules/28-elementor-block-editor-plan.md §7x (padding/margin)
     and §7aa (this restructure — width/border/background/responsive). --}}
@php
  $s = $style ?? [];
  $cols = $layout['columns'] ?? [];
  $hide = $layout['hide'] ?? [];
  $breakpoints = ['desktop' => 'Desktop', 'laptop' => 'Laptop', 'tablet' => 'Tablet', 'mobile' => 'Mobile'];
  $icons = ['desktop' => 'bi-display', 'laptop' => 'bi-laptop', 'tablet' => 'bi-tablet', 'mobile' => 'bi-phone'];
  $tabId = preg_replace('/[^a-zA-Z0-9]/', '-', $prefix);

  // One connected 4-box strip per spacing/border/radius property — still
  // stored as [style][{property}_{top|bottom|left|right}]. Bootstrap's
  // .input-group already merges adjacent borders/corners for free, no
  // bespoke CSS needed to make 4 separate inputs read as one connected
  // strip. Order requested: top, bottom, left, right (not CSS shorthand
  // order). $max lets Border Width use a tighter cap than padding/margin/
  // radius (see PageRenderService::sanitizeStyle()'s $borderPx).
  $spacingSides = ['top' => 'T', 'bottom' => 'B', 'left' => 'L', 'right' => 'R'];
  $spacingSideLabels = ['top' => 'Top', 'bottom' => 'Bottom', 'left' => 'Left', 'right' => 'Right'];
  $boxGroup = function (string $key, string $label, int $max) use ($prefix, $s, $spacingSides, $spacingSideLabels) {
    $out = '<div class="mb-2"><label class="form-label small text-muted mb-1">'.e(__($label)).'</label>';
    $out .= '<div class="input-group input-group-sm">';
    foreach ($spacingSides as $side => $abbr) {
        $name = $prefix.'[style]['.$key.'_'.$side.']';
        $val = $s[$key.'_'.$side] ?? '';
        // The T/B/L/R letters are left untranslated (a compact universal
        // abbreviation, same convention as the "(px)" unit suffix used
        // throughout this file) — the full word is still translated in the
        // title/aria-label for screen readers and anyone unsure what a bare
        // letter means.
        $out .= '<span class="input-group-text" title="'.e(__($spacingSideLabels[$side])).'">'.e($abbr).'</span>';
        $out .= '<input type="number" min="0" max="'.$max.'" name="'.e($name).'" value="'.e($val).'" class="form-control form-control-sm" placeholder="0" aria-label="'.e(__($label)).' — '.e(__($spacingSideLabels[$side])).'">';
    }
    $out .= '</div></div>';

    return $out;
  };
@endphp

@if ($isGrid)
  <p class="small text-muted mb-1">{{ __('Columns per row') }}</p>
  <div class="row g-2 mb-3">
    @foreach ($breakpoints as $bp => $bpLabel)
      <div class="col-3">
        <label class="form-label small text-muted mb-1"><i class="bi {{ $icons[$bp] }}"></i> {{ __($bpLabel) }}</label>
        <input type="number" min="1" max="6" name="{{ $prefix }}[layout][columns][{{ $bp }}]" value="{{ $cols[$bp] ?? '' }}" class="form-control form-control-sm" placeholder="—">
      </div>
    @endforeach
  </div>
@else
  <p class="small text-muted mb-3 fst-italic">{{ __('This block has no grid — column count doesn’t apply.') }}</p>
@endif

{{-- ── Layout ─────────────────────────────────────────────────────────── --}}
<div class="mb-1">
  <button type="button" class="btn btn-sm btn-link text-decoration-none px-0 fw-semibold w-100 text-start d-flex justify-content-between align-items-center js-adv-section-toggle"
          data-bs-toggle="collapse" data-bs-target="#adv-layout-{{ $tabId }}" aria-expanded="true" aria-controls="adv-layout-{{ $tabId }}">
    <span>{{ __('Layout') }}</span>
    <i class="bi bi-chevron-down small" aria-hidden="true"></i>
  </button>
  <div class="collapse show" id="adv-layout-{{ $tabId }}">
    <div class="pt-1 pb-2">
      {!! $boxGroup('margin', 'Margin (px)', 400) !!}
      {!! $boxGroup('padding', 'Padding (px)', 400) !!}
      <div class="mb-2">
        <label class="form-label small text-muted mb-1">{{ __('Width') }}</label>
        <select name="{{ $prefix }}[style][width_mode]" class="form-select form-select-sm">
          <option value="default" @selected(empty($s['width_mode']) || ($s['width_mode'] ?? '') === 'default')>{{ __('Default') }}</option>
          <option value="full" @selected(($s['width_mode'] ?? '') === 'full')>{{ __('Full Width') }}</option>
          <option value="inline" @selected(($s['width_mode'] ?? '') === 'inline')>{{ __('Inline (Auto)') }}</option>
          <option value="custom" @selected(($s['width_mode'] ?? '') === 'custom')>{{ __('Custom') }}</option>
        </select>
      </div>
      <div class="mb-2" data-depends-on="style.width_mode" data-depends-values="custom" @if(($s['width_mode'] ?? '') !== 'custom') style="display:none" @endif>
        <label class="form-label small text-muted mb-1">{{ __('Custom Width') }}</label>
        <div class="input-group input-group-sm">
          <input type="number" min="0" max="1000" step="0.1" name="{{ $prefix }}[style][width_value]" value="{{ $s['width_value'] ?? '' }}" class="form-control form-control-sm">
          <select name="{{ $prefix }}[style][width_unit]" class="form-select form-select-sm" style="max-width:85px;">
            <option value="%" @selected(($s['width_unit'] ?? '%') === '%')>%</option>
            <option value="px" @selected(($s['width_unit'] ?? '') === 'px')>px</option>
            <option value="em" @selected(($s['width_unit'] ?? '') === 'em')>em</option>
            <option value="rem" @selected(($s['width_unit'] ?? '') === 'rem')>rem</option>
          </select>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- ── Border ─────────────────────────────────────────────────────────── --}}
<div class="mb-1">
  <button type="button" class="btn btn-sm btn-link text-decoration-none px-0 fw-semibold w-100 text-start d-flex justify-content-between align-items-center js-adv-section-toggle"
          data-bs-toggle="collapse" data-bs-target="#adv-border-{{ $tabId }}" aria-expanded="false" aria-controls="adv-border-{{ $tabId }}">
    <span>{{ __('Border') }}</span>
    <i class="bi bi-chevron-down small" aria-hidden="true"></i>
  </button>
  <div class="collapse" id="adv-border-{{ $tabId }}">
    <div class="pt-1 pb-2">
      <div class="mb-2">
        <label class="form-label small text-muted mb-1">{{ __('Border Type') }}</label>
        <select name="{{ $prefix }}[style][border_style]" class="form-select form-select-sm">
          <option value="none" @selected(empty($s['border_style']) || ($s['border_style'] ?? '') === 'none')>{{ __('None') }}</option>
          <option value="solid" @selected(($s['border_style'] ?? '') === 'solid')>{{ __('Solid') }}</option>
          <option value="dashed" @selected(($s['border_style'] ?? '') === 'dashed')>{{ __('Dashed') }}</option>
          <option value="dotted" @selected(($s['border_style'] ?? '') === 'dotted')>{{ __('Dotted') }}</option>
          <option value="double" @selected(($s['border_style'] ?? '') === 'double')>{{ __('Double') }}</option>
        </select>
      </div>
      <div data-depends-on="style.border_style" data-depends-values="solid,dashed,dotted,double"
           @if(empty($s['border_style']) || $s['border_style'] === 'none') style="display:none" @endif>
        {!! $boxGroup('border_width', 'Border Width (px)', 50) !!}
        <div class="mb-2">
          <label class="form-label small text-muted mb-1">{{ __('Border Color') }}</label>
          <div class="input-group input-group-sm js-color-pair">
            <input type="color" class="form-control form-control-color js-color-swatch" value="{{ ($s['border_color'] ?? null) ?: '#000000' }}">
            <input type="text" name="{{ $prefix }}[style][border_color]" value="{{ $s['border_color'] ?? '' }}" class="form-control js-color-text" placeholder="{{ __('None') }}" maxlength="9">
          </div>
        </div>
      </div>
      {!! $boxGroup('radius', 'Border Radius (px)', 400) !!}
      <div class="mb-2">
        <label class="form-label small text-muted mb-1">{{ __('Shadow') }}</label>
        <select name="{{ $prefix }}[style][shadow]" class="form-select form-select-sm">
          <option value="" @selected(empty($s['shadow']))>{{ __('None') }}</option>
          <option value="sm" @selected(($s['shadow'] ?? '') === 'sm')>{{ __('Small') }}</option>
          <option value="md" @selected(($s['shadow'] ?? '') === 'md')>{{ __('Medium') }}</option>
          <option value="lg" @selected(($s['shadow'] ?? '') === 'lg')>{{ __('Large') }}</option>
        </select>
      </div>
    </div>
  </div>
</div>

{{-- ── Background ─────────────────────────────────────────────────────── --}}
<div class="mb-1">
  <button type="button" class="btn btn-sm btn-link text-decoration-none px-0 fw-semibold w-100 text-start d-flex justify-content-between align-items-center js-adv-section-toggle"
          data-bs-toggle="collapse" data-bs-target="#adv-bg-{{ $tabId }}" aria-expanded="false" aria-controls="adv-bg-{{ $tabId }}">
    <span>{{ __('Background') }}</span>
    <i class="bi bi-chevron-down small" aria-hidden="true"></i>
  </button>
  <div class="collapse" id="adv-bg-{{ $tabId }}">
    <div class="pt-1 pb-2">
      <div class="mb-2">
        <label class="form-label small text-muted mb-1">{{ __('Background color') }}</label>
        <div class="input-group input-group-sm js-color-pair">
          <input type="color" class="form-control form-control-color js-color-swatch" value="{{ ($s['bg_color'] ?? null) ?: '#ffffff' }}">
          <input type="text" name="{{ $prefix }}[style][bg_color]" value="{{ $s['bg_color'] ?? '' }}" class="form-control js-color-text" placeholder="{{ __('None') }}" maxlength="9">
        </div>
      </div>
      <div class="mb-2">
        <label class="form-label small text-muted mb-1">{{ __('Background image URL') }}</label>
        <input type="text" name="{{ $prefix }}[style][bg_image]" value="{{ $s['bg_image'] ?? '' }}" class="form-control form-control-sm" placeholder="https://…">
      </div>
      <div class="mb-2">
        <label class="form-label small text-muted mb-1 d-flex justify-content-between">
          <span>{{ __('Overlay darkness') }}</span>
          <span class="text-muted">{{ $s['bg_overlay'] ?? 0 }}%</span>
        </label>
        <input type="range" min="0" max="100" name="{{ $prefix }}[style][bg_overlay]" value="{{ $s['bg_overlay'] ?? 0 }}" class="form-range js-range-echo">
      </div>
    </div>
  </div>
</div>

{{-- ── Responsive ─────────────────────────────────────────────────────── --}}
<div class="mb-1">
  <button type="button" class="btn btn-sm btn-link text-decoration-none px-0 fw-semibold w-100 text-start d-flex justify-content-between align-items-center js-adv-section-toggle"
          data-bs-toggle="collapse" data-bs-target="#adv-responsive-{{ $tabId }}" aria-expanded="false" aria-controls="adv-responsive-{{ $tabId }}">
    <span>{{ __('Responsive') }}</span>
    <i class="bi bi-chevron-down small" aria-hidden="true"></i>
  </button>
  <div class="collapse" id="adv-responsive-{{ $tabId }}">
    <div class="pt-1 pb-2 d-flex flex-column gap-2">
      @foreach ($breakpoints as $bp => $bpLabel)
        @php $hideId = 'hide-'.$tabId.'-'.$bp; @endphp
        <div class="d-flex align-items-center justify-content-between">
          <label class="form-check-label small mb-0" for="{{ $hideId }}"><i class="bi {{ $icons[$bp] }}" aria-hidden="true"></i> {{ __('Hide on') }} {{ __($bpLabel) }}</label>
          <div class="form-check form-switch mb-0">
            <input type="hidden" name="{{ $prefix }}[layout][hide][{{ $bp }}]" value="0">
            <input type="checkbox" role="switch" name="{{ $prefix }}[layout][hide][{{ $bp }}]" value="1" class="form-check-input" id="{{ $hideId }}" @checked(! empty($hide[$bp]))>
          </div>
        </div>
      @endforeach
    </div>
  </div>
</div>
