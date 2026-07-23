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
  @endphp
  @if ($f['input'] === 'checkbox')
    {{-- Own hidden+checkbox pair (0/1) instead of the generic label
         wrapper below — matches the Layout tab's "Hide on" checkboxes. --}}
    <div class="mb-2 form-check">
      <input type="hidden" name="{{ $name }}" value="0">
      <input type="checkbox" name="{{ $name }}" value="1" class="form-check-input" id="chk-{{ $sanitizedPrefix }}-{{ $f['key'] }}" @checked(!empty($val))>
      <label class="form-check-label small" for="chk-{{ $sanitizedPrefix }}-{{ $f['key'] }}">{{ $f['label'] }}</label>
    </div>
  @else
    <div class="mb-2">
      <label class="form-label small text-muted mb-1">{{ $f['label'] }}</label>
      @if ($isRichText)
        <div id="quill-{{ $sanitizedPrefix }}-html" class="quill-editor" style="height: 200px;"></div>
        <input type="hidden" name="{{ $name }}" value="{{ $val }}">
      @elseif ($f['input'] === 'textarea')
        <textarea name="{{ $name }}" rows="3" class="form-control form-control-sm">{{ $val }}</textarea>
      @elseif ($f['input'] === 'select')
        <select name="{{ $name }}" class="form-select form-select-sm">
          @foreach ($f['options'] as $ov => $ol)<option value="{{ $ov }}" @selected($val === $ov)>{{ $ol }}</option>@endforeach
        </select>
      @elseif ($f['input'] === 'number')
        <input type="number" name="{{ $name }}" value="{{ $val }}" class="form-control form-control-sm" @if(isset($f['placeholder'])) placeholder="{{ $f['placeholder'] }}" @endif>
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
