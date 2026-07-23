{{-- Renders the inputs for one block. Vars: $prefix, $type, $data, $spec --}}
@php
    $fields = $spec[$type] ?? [];
    $sanitizedPrefix = preg_replace('/[^a-zA-Z0-9]/', '-', $prefix);
@endphp
<input type="hidden" name="{{ $prefix }}[type]" value="{{ $type }}">
@foreach ($fields as $f)
  @php
    $val = $data[$f['key']] ?? '';
    $name = $prefix . '[data][' . $f['key'] . ']';
    // Both block types render this field as raw HTML on the public site
    // (public/blocks/render.blade.php), so both get the Quill WYSIWYG.
    $isRichText = in_array($type, ['richtext', 'image_text'], true) && $f['key'] === 'html';
    // '' (never touched, $data has no key) vs '0' (explicitly saved
    // unchecked) are different — only fall back to the field's own
    // 'default' for the former, so a spec-level default (e.g. video's
    // "Player Controls" starts ON) doesn't fight a real saved value.
    $boolChecked = $val !== '' ? ! empty($val) : ! empty($f['default'] ?? false);
    // Optional per-field conditional visibility — e.g. video's "External
    // URL" only makes sense when Source != Self Hosted. Evaluated by
    // applyFieldDependencies() in edit.blade.php against the current value
    // of the named control (a <select>, not a checkbox/switch — see that
    // function's own comment for why boolean-field dependencies aren't
    // supported here).
    $depends = $f['depends_on'] ?? null;
    $dependsAttrs = $depends ? ' data-depends-on="'.e($depends['key']).'" data-depends-values="'.e(implode(',', (array) ($depends['values'] ?? []))).'"' : '';
  @endphp
  @if ($f['input'] === 'checkbox' || $f['input'] === 'switch')
    {{-- Own hidden+checkbox pair (0/1) instead of the generic label
         wrapper below — matches the Layout tab's "Hide on" checkboxes.
         'switch' is the same pair, styled as a pill toggle (form-switch). --}}
    <div class="mb-2 form-check{{ $f['input'] === 'switch' ? ' form-switch' : '' }}"{!! $dependsAttrs !!}>
      <input type="hidden" name="{{ $name }}" value="0">
      <input type="checkbox" name="{{ $name }}" value="1" class="form-check-input" id="chk-{{ $sanitizedPrefix }}-{{ $f['key'] }}" @checked($boolChecked)>
      <label class="form-check-label small" for="chk-{{ $sanitizedPrefix }}-{{ $f['key'] }}">{{ $f['label'] }}</label>
    </div>
  @else
    <div class="mb-2"{!! $dependsAttrs !!}>
      <label class="form-label small text-muted mb-1">{{ $f['label'] }}</label>
      @if ($isRichText)
        <div id="quill-{{ $sanitizedPrefix }}-html" class="quill-editor" style="height: 200px;"></div>
        <input type="hidden" name="{{ $name }}" value="{{ $val }}">
      @elseif ($f['input'] === 'textarea')
        <textarea name="{{ $name }}" rows="3" class="form-control form-control-sm">{{ $val }}</textarea>
      @elseif ($f['input'] === 'select')
        @php $selectedVal = $val !== '' ? $val : ($f['default_value'] ?? null); @endphp
        <select name="{{ $name }}" class="form-select form-select-sm">
          @foreach ($f['options'] as $ov => $ol)<option value="{{ $ov }}" @selected($selectedVal === $ov)>{{ $ol }}</option>@endforeach
        </select>
      @elseif ($f['input'] === 'number')
        <input type="number" name="{{ $name }}" value="{{ $val }}" class="form-control form-control-sm" @if(isset($f['placeholder'])) placeholder="{{ $f['placeholder'] }}" @endif>
      @elseif ($f['input'] === 'media')
        {{-- Plain URL text field (unchanged behavior — any absolute/relative
             URL still works, e.g. a CDN link) plus a "Browse" button that
             opens the Media Library modal (edit.blade.php) targeting this
             input. openMediaPicker() fills the input's value and fires a
             native 'input' event, so the existing preview/dirty-tracking
             logic reacts exactly as if the user had typed the URL. --}}
        <div class="input-group input-group-sm">
          <input type="text" name="{{ $name }}" value="{{ $val }}" class="form-control form-control-sm media-field-input" @if(isset($f['placeholder'])) placeholder="{{ $f['placeholder'] }}" @endif>
          <button type="button" class="btn btn-outline-secondary btn-sm" onclick="openMediaPicker(this)">{{ __('Browse') }}</button>
        </div>
      @else
        <input type="text" name="{{ $name }}" value="{{ $val }}" class="form-control form-control-sm" @if(isset($f['placeholder'])) placeholder="{{ $f['placeholder'] }}" @endif>
      @endif
    </div>
  @endif
@endforeach
{{-- Quill itself is initialized once, for every .quill-editor container on
     the page (including ones cloned later via "Add block"), by
     initQuillEditors() in edit.blade.php — not here. A per-field inline
     script wouldn't run for blocks added after page load (scripts inserted
     via innerHTML are inert), and initQuillEditors() is idempotent so it's
     safe to call again whenever a block is added. --}}
