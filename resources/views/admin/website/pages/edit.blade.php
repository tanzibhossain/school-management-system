@extends('layouts.admin')
@section('title', __('Edit Page'))
@section('content')
  @php
    $admissionFieldDefaults = [
        'last_name'        => ['label' => 'Last name',        'required' => false],
        'blood_group'      => ['label' => 'Blood group',      'required' => false],
        'student_phone'    => ['label' => 'Student phone',    'required' => false],
        'photo'            => ['label' => 'Student photo',    'required' => false],
        'guardian'         => ['label' => 'Guardian information', 'required' => false],
        'permanent_address'=> ['label' => 'Permanent address','required' => false],
        'notes'            => ['label' => 'Notes',            'required' => false],
    ];

    $spec = [
      'hero'          => [['key'=>'title','label'=>'Title','input'=>'text'],['key'=>'subtitle','label'=>'Subtitle','input'=>'text'],['key'=>'image','label'=>'Background image URL','input'=>'text'],['key'=>'button_text','label'=>'Button text','input'=>'text'],['key'=>'button_url','label'=>'Button URL','input'=>'text']],
      'heading'       => [['key'=>'text','label'=>'Text','input'=>'text'],['key'=>'align','label'=>'Align','input'=>'select','options'=>['start'=>'Left','center'=>'Center','end'=>'Right']]],
      'richtext'      => [['key'=>'heading','label'=>'Heading','input'=>'text'],['key'=>'html','label'=>'Content (HTML allowed)','input'=>'textarea']],
      'image'         => [['key'=>'url','label'=>'Image URL','input'=>'text'],['key'=>'caption','label'=>'Caption','input'=>'text']],
      'image_text'    => [['key'=>'image','label'=>'Image URL','input'=>'text'],['key'=>'heading','label'=>'Heading','input'=>'text'],['key'=>'html','label'=>'Content','input'=>'textarea'],['key'=>'image_side','label'=>'Image side','input'=>'select','options'=>['left'=>'Left','right'=>'Right']]],
      'staff'         => [['key'=>'heading','label'=>'Heading','input'=>'text']],
      'notices'       => [['key'=>'heading','label'=>'Heading','input'=>'text'],['key'=>'limit','label'=>'Max items','input'=>'number']],
      'stats'         => [['key'=>'heading','label'=>'Heading','input'=>'text']],
      'gallery_photo' => [['key'=>'heading','label'=>'Heading','input'=>'text'],['key'=>'images','label'=>'Image URLs (one per line)','input'=>'textarea']],
      'gallery_video' => [['key'=>'heading','label'=>'Heading','input'=>'text'],['key'=>'videos','label'=>'Embed URLs (one per line)','input'=>'textarea']],
      'admission_form'=> [['key'=>'heading','label'=>'Heading','input'=>'text'],['key'=>'intro','label'=>'Intro text','input'=>'text']],
      'contact'       => [['key'=>'heading','label'=>'Heading','input'=>'text'],['key'=>'address','label'=>'Address','input'=>'text'],['key'=>'phone','label'=>'Phone','input'=>'text'],['key'=>'email','label'=>'Email','input'=>'text'],['key'=>'map_embed','label'=>'Map embed URL','input'=>'text']],
      'quick_links'   => [['key'=>'heading','label'=>'Heading','input'=>'text'],['key'=>'links','label'=>'Links (Label|URL per line)','input'=>'textarea']],
      'office_hours'  => [['key'=>'heading','label'=>'Heading','input'=>'text'],['key'=>'lines','label'=>'Rows (Label|Value per line)','input'=>'textarea']],
      'contact_info'  => [['key'=>'heading','label'=>'Heading','input'=>'text'],['key'=>'address','label'=>'Address','input'=>'text'],['key'=>'phone','label'=>'Phone','input'=>'text'],['key'=>'email','label'=>'Email','input'=>'text']],
      'recent_notices'=> [['key'=>'heading','label'=>'Heading','input'=>'text'],['key'=>'limit','label'=>'Max items','input'=>'number']],
    ];

    // Block types whose content is a repeating grid of cards — these get the
    // Layout tab's per-breakpoint "columns per row" control; every other
    // block type is single-content and only gets visibility toggles.
    $gridTypes = ['staff', 'notices', 'stats', 'gallery_photo', 'gallery_video'];
  @endphp

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item">{{ __('Website') }}</li><li class="breadcrumb-item"><a href="{{ route('admin.pages.index') }}" class="text-decoration-none">{{ __('Pages') }}</a></li><li class="breadcrumb-item active">{{ $page->title }}</li></ol></nav>
      <h1 class="h4 mb-0">{{ __('Edit Page') }}</h1>
    </div>
    @if ($page->status === 'published')<a class="btn btn-outline-secondary" href="{{ url('/' . $page->slug) }}" target="_blank"><i class="bi bi-box-arrow-up-right"></i> {{ __('View Live') }}</a>@endif
  </div>

  <form method="POST" action="{{ route('admin.pages.save', $page->id) }}" id="page-form">
    @csrf @method('PUT')

    <div class="row g-3">
      {{-- Editor pane --}}
      <div class="col-lg-6">
        <div class="card mb-3"><div class="card-body">
          <div class="row g-3">
            <div class="col-md-5"><label class="form-label">{{ __('Title') }} <span class="text-danger">*</span></label>
              <input name="title" class="form-control" value="{{ old('title', $page->title) }}" required></div>
            <div class="col-md-4"><label class="form-label">{{ __('Slug') }}</label>
              <div class="input-group"><span class="input-group-text">/</span>
                <input name="slug" class="form-control" value="{{ old('slug', $page->slug) }}"></div></div>
            <div class="col-md-3"><label class="form-label">{{ __('Status') }}</label>
              <select name="status" class="form-select">
                <option value="published" @selected($page->status === 'published')>{{ __('Published') }}</option>
                <option value="draft" @selected($page->status === 'draft')>{{ __('Draft') }}</option>
              </select></div>
            <div class="col-md-3"><label class="form-label">{{ __('Template') }}</label>
              <select name="template" id="tpl-select" class="form-select">
                <option value="full" @selected($view['template'] === 'full')>{{ __('Full Width') }}</option>
                <option value="sidebar" @selected($view['template'] === 'sidebar')>{{ __('With Sidebar') }}</option>
              </select></div>
          </div>
        </div></div>

        {{-- Main column blocks --}}
        <div id="main-col" class="mb-3">
          <div class="card"><div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ __('Content Blocks') }}</span>
            <div class="input-group input-group-sm" style="width:auto;">
              <select class="form-select" id="add-blocks-select">
                @foreach ($blocks as $t => $l)<option value="{{ $t }}">{{ $l }}</option>@endforeach
              </select>
              <button type="button" class="btn btn-outline-primary" onclick="addBlock('blocks', document.getElementById('add-blocks-select').value)"><i class="bi bi-plus-lg"></i> {{ __('Add') }}</button>
            </div>
          </div><div class="card-body">
            <div id="blocks-list">
              @foreach ($view['blocks'] as $i => $b)
                @include('admin.website.pages._card', ['prefix' => "blocks[$i]", 'type' => $b['type'], 'label' => $blocks[$b['type']] ?? $b['type'], 'data' => $b['data'], 'spec' => $spec, 'style' => $b['style'] ?? [], 'layout' => $b['layout'] ?? [], 'gridTypes' => $gridTypes])
              @endforeach
            </div>
            <p class="text-muted small mb-0" id="blocks-empty" @if(count($view['blocks'])) style="display:none" @endif>{{ __('No Blocks Yet — Add One Above.') }}</p>
          </div></div>
        </div>

        {{-- Sidebar column blocks --}}
        <div id="side-col" @if($view['template'] !== 'sidebar') style="display:none" @endif>
          <div class="card"><div class="card-header d-flex justify-content-between align-items-center">
            <span>{{ __('Sidebar Blocks') }}</span>
            <div class="input-group input-group-sm" style="width:auto;">
              <select class="form-select" id="add-sidebar-select">
                @foreach ($sidebarBlocks as $t => $l)<option value="{{ $t }}">{{ $l }}</option>@endforeach
              </select>
              <button type="button" class="btn btn-outline-primary" onclick="addBlock('sidebar', document.getElementById('add-sidebar-select').value)"><i class="bi bi-plus-lg"></i> {{ __('Add') }}</button>
            </div>
          </div><div class="card-body">
            <div id="sidebar-list">
              @foreach ($view['sidebar'] as $i => $b)
                @include('admin.website.pages._card', ['prefix' => "sidebar[$i]", 'type' => $b['type'], 'label' => $sidebarBlocks[$b['type']] ?? $b['type'], 'data' => $b['data'], 'spec' => $spec, 'style' => $b['style'] ?? [], 'layout' => $b['layout'] ?? [], 'gridTypes' => $gridTypes])
              @endforeach
            </div>
          </div></div>
        </div>

        <div class="mt-3 mb-3"><button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> {{ __('Save Page') }}</button>
          <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a></div>
      </div>

      {{-- Live preview pane — same render pipeline as the public site, fed from
           this form's current (unsaved) values. See docs/modules/28-elementor-block-editor-plan.md. --}}
      <div class="col-lg-6">
        <div class="card sticky-top" style="top:1rem;">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-eye"></i> {{ __('Live Preview') }}</span>
            <span class="small text-muted" id="preview-status"></span>
          </div>
          <div class="card-body p-0">
            <iframe id="preview-frame" title="{{ __('Live Preview') }}" sandbox="allow-same-origin allow-scripts" style="width:100%;height:82vh;border:0;display:block;"></iframe>
          </div>
        </div>
      </div>
    </div>
  </form>

  {{-- Hidden block templates for the "Add" buttons (prefix placeholder __I__) --}}
  @foreach ($blocks as $t => $l)
    <template id="tpl-blocks-{{ $t }}">@include('admin.website.pages._card', ['prefix' => 'blocks[__I__]', 'type' => $t, 'label' => $l, 'data' => [], 'spec' => $spec, 'style' => [], 'layout' => [], 'gridTypes' => $gridTypes])</template>
  @endforeach
  @foreach ($sidebarBlocks as $t => $l)
    <template id="tpl-sidebar-{{ $t }}">@include('admin.website.pages._card', ['prefix' => 'sidebar[__I__]', 'type' => $t, 'label' => $l, 'data' => [], 'spec' => $spec, 'style' => [], 'layout' => [], 'gridTypes' => $gridTypes])</template>
  @endforeach

  @push('scripts')
    <script>
      var blockIdx = 1000;
      function addBlock(group, type) {
        var tpl = document.getElementById('tpl-' + group + '-' + type);
        if (!tpl) return;
        var html = tpl.innerHTML.split('__I__').join(blockIdx++);
        document.getElementById(group + '-list').insertAdjacentHTML('beforeend', html);
        var empty = document.getElementById('blocks-empty'); if (empty) empty.style.display = 'none';
        initRichTextEditors();
        schedulePreview();
      }
      document.addEventListener('click', function (e) {
        var up = e.target.closest('.js-up'), down = e.target.closest('.js-down'), rm = e.target.closest('.js-remove');
        if (up) { var c = up.closest('.block-card'); if (c.previousElementSibling) c.parentNode.insertBefore(c, c.previousElementSibling); schedulePreview(); }
        if (down) { var c = down.closest('.block-card'); if (c.nextElementSibling) c.parentNode.insertBefore(c.nextElementSibling, c); schedulePreview(); }
        if (rm) { rm.closest('.block-card').remove(); schedulePreview(); }
      });
      document.getElementById('tpl-select').addEventListener('change', function () {
        var sidebar = this.value === 'sidebar';
        document.getElementById('side-col').style.display = sidebar ? '' : 'none';
        schedulePreview();
      });

      // Style tab: sync each color swatch <-> its hex text field, both ways.
      // Delegated on document so it works for block cards cloned after page load.
      document.addEventListener('input', function (e) {
        if (e.target.matches('.js-color-swatch')) {
          var text = e.target.closest('.js-color-pair').querySelector('.js-color-text');
          text.value = e.target.value;
        }
        if (e.target.matches('.js-color-text')) {
          var swatch = e.target.closest('.js-color-pair').querySelector('.js-color-swatch');
          if (/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(e.target.value)) swatch.value = e.target.value;
        }
        if (e.target.matches('.js-range-echo')) {
          var out = e.target.closest('.col-12').querySelector('label span:last-child');
          if (out) out.textContent = e.target.value + '%';
        }
      });

      // Initialize TinyMCE for rich text editors
      function initRichTextEditors() {
        if (typeof tinymce === 'undefined') return;
        document.querySelectorAll('textarea.rich-text-editor').forEach(function(el) {
          if (!el.dataset.tinymceInit) {
            el.dataset.tinymceInit = 'true';
            tinymce.init({
              target: el,
              menubar: false,
              plugins: 'link lists table',
              toolbar: 'undo redo | bold italic underline strikethrough | alignleft aligncenter alignright | bullist numlist outdent indent | link table | removeformat',
              content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; font-size: 14px; }',
              height: 200,
              promotion: false,
              branding: false,
              setup: function(editor) {
                editor.on('change input undo redo', function() {
                  editor.save();
                  schedulePreview();
                });
              }
            });
          }
        });
      }

      // Initialize on page load
      document.addEventListener('DOMContentLoaded', initRichTextEditors);

      // ── Live preview ──────────────────────────────────────────────────────
      // Debounced: serialize the whole form as it stands right now (including
      // unsaved edits) and POST it to the preview endpoint, which renders it
      // through the exact same Blade views as the real public page (see
      // PageController::preview()) — so what you see here is what publishing
      // would actually produce, not a re-implementation that could drift.
      (function () {
        var form = document.getElementById('page-form');
        var frame = document.getElementById('preview-frame');
        var statusEl = document.getElementById('preview-status');
        var previewUrl = @json(route('admin.pages.preview', $page->id));
        var timer = null;
        var inFlight = null;

        function setStatus(text) { if (statusEl) statusEl.textContent = text; }

        window.schedulePreview = function () {
          setStatus(@json(__('Editing…')));
          clearTimeout(timer);
          timer = setTimeout(runPreview, 350);
        };

        function runPreview() {
          var fd = new FormData(form);
          fd.delete('_method'); // this must stay a real POST, not spoofed to PUT
          setStatus(@json(__('Updating…')));

          var controller = new AbortController();
          if (inFlight) inFlight.abort();
          inFlight = controller;

          fetch(previewUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: controller.signal,
          }).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
          }).then(function (html) {
            frame.srcdoc = html;
            setStatus(@json(__('Up To Date')));
          }).catch(function (err) {
            if (err.name === 'AbortError') return;
            setStatus(@json(__('Preview Failed')));
          });
        }

        // Any change anywhere in the form (text/select/textarea/checkbox/
        // range/color) schedules a re-render. Delegated so it also covers
        // block cards added/cloned after page load.
        form.addEventListener('input', schedulePreview);
        form.addEventListener('change', schedulePreview);

        document.addEventListener('DOMContentLoaded', schedulePreview);
        if (document.readyState !== 'loading') schedulePreview();
      })();
    </script>
  @endpush
@endsection
