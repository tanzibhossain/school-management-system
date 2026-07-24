{{-- Universal per-block "Layout" tab — spacing (padding/margin), columns
     (grid blocks only), and responsive visibility (every block).
     Vars: $prefix, $layout, $style, $isGrid --}}
@php
  $s = $style ?? [];
  $cols = $layout['columns'] ?? [];
  $hide = $layout['hide'] ?? [];
  $breakpoints = ['desktop' => 'Desktop', 'laptop' => 'Laptop', 'tablet' => 'Tablet', 'mobile' => 'Mobile'];
  $icons = ['desktop' => 'bi-display', 'laptop' => 'bi-laptop', 'tablet' => 'bi-tablet', 'mobile' => 'bi-phone'];
  // One connected 4-box strip per spacing property — still stored as
  // [style][{padding|margin}_{top|bottom|left|right}] (see the note at the
  // top of _style_fields.blade.php), just rendered here since spacing is a
  // layout concern. Bootstrap's .input-group already merges adjacent
  // borders/corners into one continuous bar for free — no bespoke CSS
  // needed to make four inputs read as a single connected field.
  $spacingRows = [
    ['key' => 'padding', 'label' => 'Padding (px)'],
    ['key' => 'margin', 'label' => 'Margin (px)'],
  ];
  // Order requested: top, bottom, left, right (not CSS shorthand order).
  $spacingSides = ['top' => 'T', 'bottom' => 'B', 'left' => 'L', 'right' => 'R'];
  $spacingSideLabels = ['top' => 'Top', 'bottom' => 'Bottom', 'left' => 'Left', 'right' => 'Right'];
@endphp

<p class="small text-muted mb-1">{{ __('Spacing') }}</p>
<div class="mb-3">
  @foreach ($spacingRows as $row)
    <div class="mb-2">
      <label class="form-label small text-muted mb-1">{{ __($row['label']) }}</label>
      <div class="input-group input-group-sm">
        @foreach ($spacingSides as $side => $abbr)
          {{-- The T/B/L/R letters themselves are left untranslated (a
               compact universal abbreviation, same convention as "(px)" unit
               suffixes elsewhere in this file) — the full word is still
               translated in the tooltip/aria-label for screen readers and
               anyone unsure what a bare letter means. --}}
          <span class="input-group-text" title="{{ __($spacingSideLabels[$side]) }}">{{ $abbr }}</span>
          <input type="number" min="0" max="400"
                 name="{{ $prefix }}[style][{{ $row['key'] }}_{{ $side }}]"
                 value="{{ $s[$row['key'].'_'.$side] ?? '' }}"
                 class="form-control form-control-sm"
                 placeholder="0"
                 aria-label="{{ __($row['label']) }} — {{ __($spacingSideLabels[$side]) }}">
        @endforeach
      </div>
    </div>
  @endforeach
</div>

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

<p class="small text-muted mb-1">{{ __('Hide on') }}</p>
<div class="d-flex flex-wrap gap-3">
  @foreach ($breakpoints as $bp => $bpLabel)
    <div class="form-check">
      <input type="hidden" name="{{ $prefix }}[layout][hide][{{ $bp }}]" value="0">
      <input type="checkbox" name="{{ $prefix }}[layout][hide][{{ $bp }}]" value="1" class="form-check-input" id="hide-{{ preg_replace('/[^a-zA-Z0-9]/', '-', $prefix) }}-{{ $bp }}" @checked(! empty($hide[$bp]))>
      <label class="form-check-label small" for="hide-{{ preg_replace('/[^a-zA-Z0-9]/', '-', $prefix) }}-{{ $bp }}"><i class="bi {{ $icons[$bp] }}"></i> {{ __($bpLabel) }}</label>
    </div>
  @endforeach
</div>
