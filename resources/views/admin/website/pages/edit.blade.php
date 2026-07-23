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

    // Icons for the compact block-rail rows (Bootstrap Icons).
    $blockIcons = [
      'hero' => 'bi-image', 'heading' => 'bi-type-h1', 'richtext' => 'bi-file-text',
      'image' => 'bi-image', 'image_text' => 'bi-layout-text-sidebar-reverse', 'staff' => 'bi-people',
      'notices' => 'bi-megaphone', 'stats' => 'bi-bar-chart', 'gallery_photo' => 'bi-images',
      'gallery_video' => 'bi-camera-video', 'admission_form' => 'bi-clipboard-check', 'contact' => 'bi-envelope',
      'quick_links' => 'bi-link-45deg', 'office_hours' => 'bi-clock', 'contact_info' => 'bi-telephone',
      'recent_notices' => 'bi-bell',
    ];
  @endphp

  <style>
    /* Block rail — compact rows by default, one settings panel open at a time
       (Elementor-style "layers" list). See Milestone 3 in
       docs/modules/28-elementor-block-editor-plan.md. */
    .block-row { cursor: pointer; user-select: none; }
    .block-row:hover { background: var(--bs-tertiary-bg, #f8f9fa); }
    .block-card.is-open { border-color: var(--bs-primary); box-shadow: 0 0 0 .15rem rgba(13,110,253,.12); }
    .js-block-chevron { transition: transform .15s ease; }
    .block-card.is-open .js-block-chevron { transform: rotate(180deg); }
    .js-drag-handle { cursor: grab; }

    /* Responsive viewport toolbar — resizes the preview iframe to simulate a
       breakpoint, so the Layout tab's per-breakpoint columns/visibility
       controls are visibly testable. Widths mirror the Bootstrap infixes
       BlockPresentation maps breakpoints onto (base/-md/-lg/-xl). */
    #preview-viewport-wrap { background: var(--bs-tertiary-bg, #f1f3f5); transition: padding .15s ease; }
    #preview-frame { background: #fff; transition: width .2s ease; }
    #preview-viewport-wrap.vp-laptop { padding: 0; }
    #preview-viewport-wrap.vp-laptop #preview-frame { width: 1200px; max-width: 100%; }
    #preview-viewport-wrap.vp-tablet #preview-frame { width: 768px; max-width: 100%; box-shadow: 0 0 0 1px var(--bs-border-color); }
    #preview-viewport-wrap.vp-mobile #preview-frame { width: 375px; max-width: 100%; border-radius: 14px; box-shadow: 0 0 0 1px var(--bs-border-color); }
    #preview-viewport-wrap.vp-tablet, #preview-viewport-wrap.vp-mobile { padding: 1rem 0; }
  </style>

  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <nav><ol class="breadcrumb small mb-1"><li class="breadcrumb-item">{{ __('Website') }}</li><li class="breadcrumb-item"><a href="{{ route('admin.pages.index') }}" class="text-decoration-none">{{ __('Pages') }}</a></li><li class="breadcrumb-item active">{{ $page->title }}</li></ol></nav>
      <h1 class="h4 mb-0">{{ __('Edit Page') }}</h1>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-outline-secondary" href="{{ route('admin.pages.history', $page->id) }}"><i class="bi bi-clock-history"></i> {{ __('History') }}</a>
      @if ($page->status === 'published')<a class="btn btn-outline-secondary" href="{{ url('/' . $page->slug) }}" target="_blank"><i class="bi bi-box-arrow-up-right"></i> {{ __('View Live') }}</a>@endif
    </div>
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
                @include('admin.website.pages._card', ['prefix' => "blocks[$i]", 'type' => $b['type'], 'label' => $blocks[$b['type']] ?? $b['type'], 'data' => $b['data'], 'spec' => $spec, 'style' => $b['style'] ?? [], 'layout' => $b['layout'] ?? [], 'gridTypes' => $gridTypes, 'icon' => $blockIcons[$b['type']] ?? 'bi-square'])
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
                @include('admin.website.pages._card', ['prefix' => "sidebar[$i]", 'type' => $b['type'], 'label' => $sidebarBlocks[$b['type']] ?? $b['type'], 'data' => $b['data'], 'spec' => $spec, 'style' => $b['style'] ?? [], 'layout' => $b['layout'] ?? [], 'gridTypes' => $gridTypes, 'icon' => $blockIcons[$b['type']] ?? 'bi-square'])
              @endforeach
            </div>
          </div></div>
        </div>

        <div class="mt-3 mb-3">
          <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> {{ __('Save Page') }}</button>
          <button type="button" class="btn btn-outline-secondary" id="btn-undo" title="{{ __('Undo (Ctrl+Z)') }}" disabled><i class="bi bi-arrow-counterclockwise"></i></button>
          <button type="button" class="btn btn-outline-secondary" id="btn-redo" title="{{ __('Redo (Ctrl+Y)') }}" disabled><i class="bi bi-arrow-clockwise"></i></button>
          <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
        </div>
      </div>

      {{-- Live preview pane — same render pipeline as the public site, fed from
           this form's current (unsaved) values. See docs/modules/28-elementor-block-editor-plan.md. --}}
      <div class="col-lg-6">
        <div class="card sticky-top" style="top:1rem;">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="bi bi-eye"></i> {{ __('Live Preview') }}</span>
            <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Preview Viewport') }}" id="viewport-toolbar">
              <button type="button" class="btn btn-outline-secondary active" data-viewport="desktop" title="{{ __('Desktop') }}"><i class="bi bi-display"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-viewport="laptop" title="{{ __('Laptop') }}"><i class="bi bi-laptop"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-viewport="tablet" title="{{ __('Tablet') }}"><i class="bi bi-tablet"></i></button>
              <button type="button" class="btn btn-outline-secondary" data-viewport="mobile" title="{{ __('Mobile') }}"><i class="bi bi-phone"></i></button>
            </div>
            <span class="small text-muted" id="preview-status"></span>
          </div>
          <div class="card-body p-0 d-flex justify-content-center" id="preview-viewport-wrap">
            <iframe id="preview-frame" title="{{ __('Live Preview') }}" sandbox="allow-same-origin allow-scripts" style="width:100%;height:82vh;border:0;display:block;"></iframe>
          </div>
        </div>
      </div>
    </div>
  </form>

  {{-- Hidden block templates for the "Add" buttons (prefix placeholder __I__) --}}
  @foreach ($blocks as $t => $l)
    <template id="tpl-blocks-{{ $t }}">@include('admin.website.pages._card', ['prefix' => 'blocks[__I__]', 'type' => $t, 'label' => $l, 'data' => [], 'spec' => $spec, 'style' => [], 'layout' => [], 'gridTypes' => $gridTypes, 'icon' => $blockIcons[$t] ?? 'bi-square'])</template>
  @endforeach
  @foreach ($sidebarBlocks as $t => $l)
    <template id="tpl-sidebar-{{ $t }}">@include('admin.website.pages._card', ['prefix' => 'sidebar[__I__]', 'type' => $t, 'label' => $l, 'data' => [], 'spec' => $spec, 'style' => [], 'layout' => [], 'gridTypes' => $gridTypes, 'icon' => $blockIcons[$t] ?? 'bi-square'])</template>
  @endforeach

  @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
      var blockIdx = 1000;
      function addBlock(group, type) {
        var tpl = document.getElementById('tpl-' + group + '-' + type);
        if (!tpl) return;
        var html = tpl.innerHTML.split('__I__').join(blockIdx++);
        document.getElementById(group + '-list').insertAdjacentHTML('beforeend', html);
        var empty = document.getElementById('blocks-empty'); if (empty) empty.style.display = 'none';
        initQuillEditors();
        // A style may already be copied from an earlier block — the new
        // block's Paste Style button starts disabled server-side, enable it too.
        if (copiedStyle) { document.querySelectorAll('.js-paste-style').forEach(function (b) { b.disabled = false; }); }
        // Open the newly added block's settings immediately, like Elementor
        // does when you drop a new widget — you're almost always about to
        // configure it right away.
        var list = document.getElementById(group + '-list');
        openBlockCard(list.lastElementChild);
        schedulePreview();
        pushHistory();
      }

      // Rail: only one block's Content/Style/Layout panel open at a time,
      // per list (main blocks vs sidebar blocks are independent).
      function closeBlockList(list) {
        list.querySelectorAll(':scope > .block-card').forEach(function (c) {
          c.classList.remove('is-open');
          c.querySelector('.block-settings').style.display = 'none';
        });
      }
      function openBlockCard(card) {
        if (!card) return;
        closeBlockList(card.parentElement);
        card.classList.add('is-open');
        card.querySelector('.block-settings').style.display = '';
      }
      function toggleBlockCard(card) {
        if (card.classList.contains('is-open')) {
          closeBlockList(card.parentElement);
        } else {
          openBlockCard(card);
        }
      }

      // Drag-to-reorder via the grip handle — reordering is a structural
      // change (positions shift for every block after the moved one), so it
      // always triggers a full preview reload, same as the up/down buttons.
      if (window.Sortable) {
        ['blocks-list', 'sidebar-list'].forEach(function (id) {
          var list = document.getElementById(id);
          if (!list) return;
          new Sortable(list, {
            handle: '.js-drag-handle',
            animation: 150,
            ghostClass: 'opacity-50',
            onEnd: function () { schedulePreview(); pushHistory(); },
          });
        });
      }

      // Copy/paste block style — a single "clipboard" shared across every
      // block on the page, matching Elementor Pro's copy/paste-style
      // behavior. Client-side only, no backend involvement: paste just sets
      // the target block's own Style-tab field values and re-dispatches
      // input/change so the existing swatch-sync/range-echo/preview-schedule
      // listeners all pick it up exactly as if the user had typed it.
      var copiedStyle = null;
      function styleFieldsIn(card) {
        var out = {};
        card.querySelectorAll('[name*="[style]["]').forEach(function (el) {
          var m = el.name.match(/\[style\]\[([a-zA-Z0-9_]+)\]$/);
          if (m) out[m[1]] = el.value;
        });
        return out;
      }

      // ── Undo / redo ──────────────────────────────────────────────────────
      // A history array + pointer (not separate undo/redo stacks) — the
      // classic pattern: history[historyIndex] is always what's on screen.
      // Snapshots are DATA (block type + each field's value), never raw DOM —
      // restoring rebuilds each block by cloning its <template> (exactly what
      // "Add block" already does) and filling in the captured values, so a
      // restored Quill field gets a fresh, working editor instead of Quill's
      // internal DOM baked into a dead HTML string. Session-only, never sent
      // to the server or persisted (see docs/modules/28-elementor-block-editor-plan.md).
      var history_ = [];
      var historyIndex = -1;
      var pushHistoryTimer = null;
      var HISTORY_LIMIT = 50;

      function captureCardFields(card) {
        return Array.prototype.map.call(card.querySelectorAll('.block-settings [name]'), function (el) {
          return (el.type === 'checkbox' || el.type === 'radio') ? { checked: el.checked } : { value: el.value };
        });
      }
      function applyCardFields(card, captured) {
        var els = card.querySelectorAll('.block-settings [name]');
        captured.forEach(function (c, i) {
          var el = els[i];
          if (!el) return;
          if (el.type === 'checkbox' || el.type === 'radio') { el.checked = !!c.checked; } else { el.value = c.value; }
        });
      }
      function captureList(listId) {
        var list = document.getElementById(listId);
        return Array.prototype.map.call(list.children, function (card) {
          var typeInput = card.querySelector('[name$="[type]"]');
          return { type: typeInput ? typeInput.value : '', fields: captureCardFields(card) };
        });
      }
      function snapshotState() {
        return {
          title: document.querySelector('[name="title"]').value,
          slug: document.querySelector('[name="slug"]').value,
          status: document.querySelector('[name="status"]').value,
          template: document.getElementById('tpl-select').value,
          blocks: captureList('blocks-list'),
          sidebar: captureList('sidebar-list'),
        };
      }
      function restoreList(listId, group, snapshotBlocks) {
        var list = document.getElementById(listId);
        list.innerHTML = '';
        snapshotBlocks.forEach(function (b) {
          var tpl = document.getElementById('tpl-' + group + '-' + b.type);
          if (!tpl) return;
          var html = tpl.innerHTML.split('__I__').join(blockIdx++);
          list.insertAdjacentHTML('beforeend', html);
          applyCardFields(list.lastElementChild, b.fields);
        });
      }
      function restoreSnapshot(snap) {
        document.querySelector('[name="title"]').value = snap.title;
        document.querySelector('[name="slug"]').value = snap.slug;
        document.querySelector('[name="status"]').value = snap.status;
        document.getElementById('tpl-select').value = snap.template;
        document.getElementById('side-col').style.display = snap.template === 'sidebar' ? '' : 'none';
        restoreList('blocks-list', 'blocks', snap.blocks);
        restoreList('sidebar-list', 'sidebar', snap.sidebar);
        var empty = document.getElementById('blocks-empty');
        if (empty) empty.style.display = snap.blocks.length ? 'none' : '';
        initQuillEditors();
        schedulePreview();
        updateUndoRedoButtons();
      }
      function pushHistory() {
        var snap = snapshotState();
        history_ = history_.slice(0, historyIndex + 1);
        history_.push(snap);
        if (history_.length > HISTORY_LIMIT) history_.shift();
        historyIndex = history_.length - 1;
        updateUndoRedoButtons();
      }
      function schedulePushHistory() {
        clearTimeout(pushHistoryTimer);
        pushHistoryTimer = setTimeout(pushHistory, 1200);
      }
      function undo() {
        if (historyIndex <= 0) return;
        historyIndex--;
        restoreSnapshot(history_[historyIndex]);
      }
      function redo() {
        if (historyIndex >= history_.length - 1) return;
        historyIndex++;
        restoreSnapshot(history_[historyIndex]);
      }
      function updateUndoRedoButtons() {
        var undoBtn = document.getElementById('btn-undo'), redoBtn = document.getElementById('btn-redo');
        if (undoBtn) undoBtn.disabled = historyIndex <= 0;
        if (redoBtn) redoBtn.disabled = historyIndex >= history_.length - 1;
      }
      document.getElementById('btn-undo').addEventListener('click', undo);
      document.getElementById('btn-redo').addEventListener('click', redo);
      // Ctrl/Cmd+Z / Ctrl+Y — but only when focus isn't in an editable field,
      // so the browser's own per-field undo (fixing a typo) isn't hijacked.
      document.addEventListener('keydown', function (e) {
        var mod = e.ctrlKey || e.metaKey;
        var key = e.key.toLowerCase();
        if (!mod || (key !== 'z' && key !== 'y')) return;
        var active = document.activeElement;
        var inEditable = active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.tagName === 'SELECT' || active.isContentEditable);
        if (inEditable) return;
        e.preventDefault();
        if (key === 'y' || e.shiftKey) { redo(); } else { undo(); }
      });
      // Seed the history with the page's initial state once everything (incl.
      // Quill) has settled, so the very first edit has something to undo to.
      document.addEventListener('DOMContentLoaded', function () { pushHistory(); });
      if (document.readyState !== 'loading') pushHistory();

      document.addEventListener('click', function (e) {
        var up = e.target.closest('.js-up'), down = e.target.closest('.js-down'), rm = e.target.closest('.js-remove');
        var toggle = e.target.closest('.js-block-toggle');
        var copyStyle = e.target.closest('.js-copy-style'), pasteStyle = e.target.closest('.js-paste-style');
        if (up) { var c = up.closest('.block-card'); if (c.previousElementSibling) c.parentNode.insertBefore(c, c.previousElementSibling); schedulePreview(); pushHistory(); return; }
        if (down) { var c = down.closest('.block-card'); if (c.nextElementSibling) c.parentNode.insertBefore(c.nextElementSibling, c); schedulePreview(); pushHistory(); return; }
        if (rm) { rm.closest('.block-card').remove(); schedulePreview(); pushHistory(); return; }
        if (toggle) { toggleBlockCard(toggle.closest('.block-card')); return; }
        if (copyStyle) {
          copiedStyle = styleFieldsIn(copyStyle.closest('.block-card'));
          document.querySelectorAll('.js-paste-style').forEach(function (b) { b.disabled = false; });
          copyStyle.classList.replace('btn-outline-secondary', 'btn-success');
          setTimeout(function () { copyStyle.classList.replace('btn-success', 'btn-outline-secondary'); }, 700);
          return;
        }
        if (pasteStyle) {
          if (!copiedStyle) return;
          var card = pasteStyle.closest('.block-card');
          Object.keys(copiedStyle).forEach(function (key) {
            var input = card.querySelector('[name$="[style][' + key + ']"]');
            if (!input) return;
            input.value = copiedStyle[key];
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
          });
          pasteStyle.classList.replace('btn-outline-secondary', 'btn-success');
          setTimeout(function () { pasteStyle.classList.replace('btn-success', 'btn-outline-secondary'); }, 700);
          pushHistory();
        }
      });
      document.addEventListener('keydown', function (e) {
        if ((e.key === 'Enter' || e.key === ' ') && e.target.matches('.js-block-toggle')) {
          e.preventDefault();
          toggleBlockCard(e.target.closest('.block-card'));
        }
      });
      document.getElementById('tpl-select').addEventListener('change', function () {
        var sidebar = this.value === 'sidebar';
        document.getElementById('side-col').style.display = sidebar ? '' : 'none';
        schedulePreview();
        pushHistory();
      });

      // Responsive viewport toolbar — resizes the preview iframe only, no re-render needed.
      document.getElementById('viewport-toolbar').addEventListener('click', function (e) {
        var btn = e.target.closest('[data-viewport]');
        if (!btn) return;
        this.querySelectorAll('[data-viewport]').forEach(function (b) { b.classList.toggle('active', b === btn); });
        var wrap = document.getElementById('preview-viewport-wrap');
        wrap.classList.remove('vp-laptop', 'vp-tablet', 'vp-mobile');
        if (btn.dataset.viewport !== 'desktop') wrap.classList.add('vp-' + btn.dataset.viewport);
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

      // Rich text fields (richtext/image_text "html") use Quill — open
      // source (BSD-3), loaded globally from CDN in layouts/admin.blade.php,
      // no API key or build step required. One shared init, idempotent
      // (guarded by data-quill-init) so it's safe to call again after a new
      // block is added — see the comment in _fields.blade.php for why a
      // per-field inline script doesn't work for cloned blocks.
      function initQuillEditors() {
        if (typeof Quill === 'undefined') return;
        document.querySelectorAll('.quill-editor').forEach(function (container) {
          if (container.dataset.quillInit) return;
          container.dataset.quillInit = 'true';
          var hidden = container.nextElementSibling;
          if (!hidden) return;
          var quill = new Quill(container, {
            theme: 'snow',
            modules: {
              toolbar: [
                [{ header: [1, 2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['link', 'image'],
                ['clean'],
              ],
            },
            placeholder: @json(__('Enter Content…')),
          });
          quill.root.innerHTML = hidden.value;
          quill.on('text-change', function () {
            hidden.value = quill.root.innerHTML;
            var card = container.closest('.block-card');
            if (card && window.scheduleBlockPreview) { window.scheduleBlockPreview(card); } else { schedulePreview(); }
            schedulePushHistory();
          });
        });
      }
      document.addEventListener('DOMContentLoaded', initQuillEditors);

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
        var blockPreviewUrl = @json(route('admin.pages.preview-block', $page->id));
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

        // ── Per-block partial re-render ─────────────────────────────────────
        // A plain field edit inside one block's Content/Style/Layout tabs
        // doesn't need the whole iframe reloaded — just that one element
        // patched in place. Falls back to the full runPreview() above for
        // anything structural (add/remove/reorder/template — those already
        // call schedulePreview() directly) or if anything about the
        // lightweight path doesn't check out (iframe not settled yet, target
        // element missing, request fails) so the preview never gets stuck.
        function blockFormData(card) {
          var fd = new FormData();
          card.querySelectorAll('[name]').forEach(function (el) {
            if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
            fd.append(el.name.replace(/^(blocks|sidebar)\[\d+\]/, 'block'), el.value);
          });
          return fd;
        }

        function scheduleBlockPreview(card) {
          setStatus(@json(__('Editing…')));
          clearTimeout(card._previewTimer);
          card._previewTimer = setTimeout(function () { runBlockPreview(card); }, 350);
        }
        window.scheduleBlockPreview = scheduleBlockPreview;

        function runBlockPreview(card) {
          var named = card.querySelector('[name]');
          var frameDoc;
          try { frameDoc = frame.contentDocument; } catch (e) { frameDoc = null; }
          if (!named || !frameDoc) { schedulePreview(); return; }

          var group = /^sidebar\[/.test(named.name) ? 'sidebar' : 'blocks';
          var list = document.getElementById(group === 'sidebar' ? 'sidebar-list' : 'blocks-list');
          var index = Array.prototype.indexOf.call(list.children, card);
          var target = index < 0 ? null : frameDoc.querySelector('[data-block-group="' + group + '"][data-block-index="' + index + '"]');
          if (!target) { schedulePreview(); return; } // never rendered there yet — do a full reload instead

          var fd = blockFormData(card);
          fd.append('group', group);
          fd.append('contained', (group === 'blocks' && document.getElementById('tpl-select').value === 'sidebar') ? '1' : '0');
          setStatus(@json(__('Updating…')));

          fetch(blockPreviewUrl, {
            method: 'POST',
            body: fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
          }).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
          }).then(function (html) {
            var tmp = frameDoc.createElement('div');
            tmp.innerHTML = html.trim();
            var next = tmp.firstElementChild;
            if (!next) throw new Error('empty block render');
            next.setAttribute('data-block-index', index);
            next.setAttribute('data-block-group', group);
            target.replaceWith(next);
            setStatus(@json(__('Up To Date')));
          }).catch(function () {
            // Something about the fast path failed — fall back to a full
            // reload so the preview is never left stale.
            schedulePreview();
          });
        }

        // Any change anywhere in the form schedules a re-render — a plain
        // field edit inside a block's own settings goes through the
        // lightweight single-block path, everything else (page meta,
        // template) does a full reload. Delegated so it also covers block
        // cards added/cloned after page load.
        function handleFormChange(e) {
          var card = e.target.closest('.block-card');
          var withinSettings = e.target.closest('.block-settings');
          if (card && withinSettings) {
            scheduleBlockPreview(card);
          } else {
            schedulePreview();
          }
          schedulePushHistory();
        }
        form.addEventListener('input', handleFormChange);
        form.addEventListener('change', handleFormChange);

        document.addEventListener('DOMContentLoaded', schedulePreview);
        if (document.readyState !== 'loading') schedulePreview();
      })();

      // ── Click-to-select bridge ───────────────────────────────────────────
      // The preview iframe (public/layout.blade.php) posts a message when the
      // user clicks a rendered block on the canvas; open that block's
      // settings panel in the rail. The rendered index is positional (the
      // Nth block-card currently in the list), which lines up exactly with
      // what the preview just rendered — the preview is built from this same
      // form's current DOM order (see runPreview() above).
      window.addEventListener('message', function (e) {
        if (e.origin !== window.location.origin) return;
        var msg = e.data;
        if (!msg || msg.source !== 'page-preview' || msg.type !== 'select-block') return;
        var list = document.getElementById(msg.group === 'sidebar' ? 'sidebar-list' : 'blocks-list');
        var card = list && list.children[parseInt(msg.index, 10)];
        if (card) {
          openBlockCard(card);
          card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
      });
    </script>
  @endpush
@endsection
