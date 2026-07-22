{{-- Universal per-block "Layout" tab — columns (grid blocks only) + responsive
     visibility (every block). Vars: $prefix, $layout, $isGrid --}}
@php
  $cols = $layout['columns'] ?? [];
  $hide = $layout['hide'] ?? [];
  $breakpoints = ['desktop' => 'Desktop', 'laptop' => 'Laptop', 'tablet' => 'Tablet', 'mobile' => 'Mobile'];
  $icons = ['desktop' => 'bi-display', 'laptop' => 'bi-laptop', 'tablet' => 'bi-tablet', 'mobile' => 'bi-phone'];
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
