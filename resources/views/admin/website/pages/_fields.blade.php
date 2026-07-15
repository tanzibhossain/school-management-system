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
    $isRichText = $type === 'richtext' && $f['key'] === 'html';
  @endphp
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
      <input type="number" name="{{ $name }}" value="{{ $val }}" class="form-control form-control-sm">
    @else
      <input type="text" name="{{ $name }}" value="{{ $val }}" class="form-control form-control-sm">
    @endif
  </div>
@endforeach
@if ($isRichText ?? false)
@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('quill-{{ $sanitizedPrefix }}-html');
    if (container && window.Quill) {
      var quill = new Quill(container, {
        theme: 'snow',
        modules: {
          toolbar: [
            [{ header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link', 'image'],
            ['clean']
          ]
        },
        placeholder: 'Enter content...'
      });
      quill.root.innerHTML = {!! json_encode($val) !!};
      quill.on('text-change', function() {
        container.nextElementSibling.value = quill.root.innerHTML;
      });
    }
  });
</script>
@endpush
@endif
