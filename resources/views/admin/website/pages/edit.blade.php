@extends('layouts.admin-fullscreen')
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
      'hero'          => [['key'=>'title','label'=>'Title','input'=>'text'],['key'=>'subtitle','label'=>'Subtitle','input'=>'text'],['key'=>'image','label'=>'Background image URL','input'=>'media'],['key'=>'button_text','label'=>'Button text','input'=>'text'],['key'=>'button_url','label'=>'Button URL','input'=>'text']],
      'heading'       => [['key'=>'text','label'=>'Text','input'=>'text'],['key'=>'align','label'=>'Align','input'=>'select','options'=>['start'=>'Left','center'=>'Center','end'=>'Right']]],
      'richtext'      => [['key'=>'heading','label'=>'Heading','input'=>'text'],['key'=>'html','label'=>'Content (HTML allowed)','input'=>'textarea']],
      'image'         => [['key'=>'url','label'=>'Image URL','input'=>'media'],['key'=>'caption','label'=>'Caption','input'=>'text']],
      'image_text'    => [['key'=>'image','label'=>'Image URL','input'=>'media'],['key'=>'heading','label'=>'Heading','input'=>'text'],['key'=>'html','label'=>'Content','input'=>'textarea'],['key'=>'image_side','label'=>'Image side','input'=>'select','options'=>['left'=>'Left','right'=>'Right']]],
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
      'video'         => [
        ['key'=>'heading','label'=>'Heading','input'=>'text'],
        ['key'=>'source','label'=>'Source','input'=>'select','options'=>['youtube'=>'YouTube','vimeo'=>'Vimeo','dailymotion'=>'Dailymotion','videopress'=>'VideoPress','self_hosted'=>'Self Hosted']],
        ['key'=>'url','label'=>'External URL','input'=>'text','placeholder'=>'https://youtube.com/watch?v=…','depends_on'=>['key'=>'source','values'=>['youtube','vimeo','dailymotion','videopress']]],
        ['key'=>'file_url','label'=>'Video File URL','input'=>'text','placeholder'=>'https://example.com/video.mp4','depends_on'=>['key'=>'source','values'=>['self_hosted']]],
        ['key'=>'start_time','label'=>'Start Time (seconds)','input'=>'number'],
        ['key'=>'end_time','label'=>'End Time (seconds)','input'=>'number'],
        ['key'=>'autoplay','label'=>'Autoplay','input'=>'switch'],
        ['key'=>'mute','label'=>'Mute','input'=>'switch'],
        ['key'=>'loop','label'=>'Loop','input'=>'switch'],
        ['key'=>'controls','label'=>'Player Controls','input'=>'switch','default'=>true],
        ['key'=>'download','label'=>'Download Button','input'=>'switch'],
        ['key'=>'preload','label'=>'Preload','input'=>'select','options'=>['none'=>'None','metadata'=>'Metadata','auto'=>'Auto'], 'default_value'=>'metadata'],
        ['key'=>'poster','label'=>'Poster Image URL','input'=>'media','placeholder'=>'https://…'],
        ['key'=>'caption','label'=>'Caption','input'=>'text'],
      ],
      'button'        => [['key'=>'text','label'=>'Button text','input'=>'text'],['key'=>'url','label'=>'Button URL','input'=>'text'],['key'=>'align','label'=>'Align','input'=>'select','options'=>['start'=>'Left','center'=>'Center','end'=>'Right']],['key'=>'open_new_tab','label'=>'Open in new tab','input'=>'checkbox']],
      'divider'       => [['key'=>'line_style','label'=>'Line style','input'=>'select','options'=>['solid'=>'Solid','dashed'=>'Dashed','dotted'=>'Dotted']],['key'=>'width_pct','label'=>'Width (%)','input'=>'number']],
      'spacer'        => [['key'=>'height','label'=>'Height (px)','input'=>'number']],
      'google_maps'   => [['key'=>'embed_url','label'=>'Map embed URL','input'=>'text'],['key'=>'height','label'=>'Height (px)','input'=>'number']],
      'icon'          => [['key'=>'icon','label'=>'Icon class','input'=>'text','placeholder'=>'bi-star'],['key'=>'size','label'=>'Size (px)','input'=>'number'],['key'=>'color','label'=>'Color (hex)','input'=>'text','placeholder'=>'#4f46e5'],['key'=>'url','label'=>'Link URL (optional)','input'=>'text'],['key'=>'align','label'=>'Align','input'=>'select','options'=>['start'=>'Left','center'=>'Center','end'=>'Right']]],
      // 'grid' has no Content-tab fields of its own — its column count comes
      // from the universal Layout tab (see $gridTypes below), same as
      // staff/notices/stats; its "fields" are its nested children instead
      // (see _nested_blocks.blade.php, included by _card.blade.php).
      'grid'          => [],
      'container'     => [['key'=>'direction','label'=>'Direction','input'=>'select','options'=>['column'=>'Stacked (column)','row'=>'Side by side (row)']],['key'=>'gap','label'=>'Gap (px)','input'=>'number']],
    ];

    // Block types whose content is a repeating grid of cards — these get the
    // Layout tab's per-breakpoint "columns per row" control; every other
    // block type is single-content and only gets visibility toggles.
    $gridTypes = ['staff', 'notices', 'stats', 'gallery_photo', 'gallery_video', 'grid'];

    // Icons for the compact block-rail rows and the Add Block picker (Bootstrap Icons).
    $blockIcons = [
      'hero' => 'bi-image', 'heading' => 'bi-type-h1', 'richtext' => 'bi-file-text',
      'image' => 'bi-image', 'image_text' => 'bi-layout-text-sidebar-reverse', 'staff' => 'bi-people',
      'notices' => 'bi-megaphone', 'stats' => 'bi-bar-chart', 'gallery_photo' => 'bi-images',
      'gallery_video' => 'bi-camera-video', 'admission_form' => 'bi-clipboard-check', 'contact' => 'bi-envelope',
      'quick_links' => 'bi-link-45deg', 'office_hours' => 'bi-clock', 'contact_info' => 'bi-telephone',
      'recent_notices' => 'bi-bell',
      'video' => 'bi-play-btn', 'button' => 'bi-hand-index-thumb', 'divider' => 'bi-hr',
      'spacer' => 'bi-arrows-expand', 'google_maps' => 'bi-geo-alt', 'icon' => 'bi-star',
      'container' => 'bi-square', 'grid' => 'bi-grid-3x3-gap',
    ];

    // Add Block picker categories — mirrors PageRenderService::CATEGORIES;
    // any BLOCKS type not explicitly placed in Layout/Basic falls into
    // Advanced automatically (see docs/modules/28-elementor-block-editor-plan.md §7d).
    $layoutTypes = \App\Modules\Website\Services\PageRenderService::CATEGORIES['layout'];
    $basicTypes = \App\Modules\Website\Services\PageRenderService::CATEGORIES['basic'];
    $advancedTypes = array_values(array_diff(array_keys($blocks), array_merge($layoutTypes, $basicTypes)));
    $blockCategories = [
      'layout' => ['label' => __('Layout'), 'types' => $layoutTypes],
      'basic' => ['label' => __('Basic'), 'types' => $basicTypes],
      'advanced' => ['label' => __('Advanced'), 'types' => $advancedTypes],
    ];
  @endphp

  <style>
    /* Fullscreen Elementor-style shell — topbar + resizable left sidebar +
       full-width scrollable canvas, no admin chrome. See "Fullscreen editor
       shell" in docs/modules/28-elementor-block-editor-plan.md. */
    .editor-shell { display: flex; flex-direction: column; height: 100vh; }
    .editor-topbar { flex: 0 0 auto; background: #fff; border-bottom: 1px solid var(--bs-border-color); }
    .editor-body { flex: 1 1 auto; display: flex; min-height: 0; }

    .editor-sidebar {
      position: relative; flex: 0 0 auto; display: flex; flex-direction: column;
      width: 12.5vw; min-width: 12.5vw; max-width: 25vw;
      background: #fff; border-right: 1px solid var(--bs-border-color);
    }
    .sidebar-resize-handle {
      /* Straddles the sidebar's right edge (half in, half out) so it's easy
         to grab from either side. Needs .editor-sidebar to NOT clip with
         overflow:hidden — it used to, which clipped away the outer half of
         this handle and left only a ~3px sliver clickable, so dragging felt
         broken. Sidebar content that needs clipping (long dropdowns, wide
         tables) gets it from .sidebar-panel's own overflow-x instead. */
      position: absolute; top: 0; right: -4px; width: 8px; height: 100%; cursor: col-resize; z-index: 5;
    }
    .sidebar-resize-handle:hover, .sidebar-resize-handle.is-dragging { background: rgba(79,70,229,.25); }
    .sidebar-panel { display: none; flex: 1 1 auto; overflow-y: auto; overflow-x: hidden; padding: .85rem; }
    .sidebar-panel.active { display: block; }

    .editor-canvas {
      flex: 1 1 auto; overflow: auto; background: var(--bs-tertiary-bg, #f1f3f5);
      display: flex; justify-content: center; align-items: stretch;
    }
    /* width lives here (not inline on the <iframe>) so the viewport-specific
       rules below — which only win via a higher-specificity selector, not
       !important — can actually override it. An inline style="width:100%"
       on the element would beat every one of these regardless of
       specificity, which is exactly what silently broke the viewport
       toolbar before this comment existed. */
    #preview-frame { background: #fff; width: 100%; transition: width .2s ease; flex: 0 0 auto; }
    .editor-canvas.vp-laptop { padding: 0; }
    .editor-canvas.vp-laptop #preview-frame { width: 1200px; max-width: 100%; }
    .editor-canvas.vp-tablet #preview-frame { width: 768px; max-width: 100%; box-shadow: 0 0 0 1px var(--bs-border-color); }
    .editor-canvas.vp-mobile #preview-frame { width: 375px; max-width: 100%; border-radius: 14px; box-shadow: 0 0 0 1px var(--bs-border-color); }
    .editor-canvas.vp-tablet, .editor-canvas.vp-mobile { padding: 1rem 0; }

    /* Block rail — compact rows by default, one settings panel open at a
       time (Elementor-style "layers" list). */
    .block-row { cursor: pointer; user-select: none; }
    .block-row:hover { background: var(--bs-tertiary-bg, #f8f9fa); }
    .block-card.is-open { border-color: var(--bs-primary); box-shadow: 0 0 0 .15rem rgba(13,110,253,.12); }
    .js-block-chevron { transition: transform .15s ease; }
    .block-card.is-open .js-block-chevron { transform: rotate(180deg); }
    .js-drag-handle { cursor: grab; }

    .js-panel-btn.active { background: var(--bs-primary); color: #fff; border-color: var(--bs-primary); }

    /* Add Block panel — search + collapsible category groups, each a
       two-column grid of icon-over-label boxes. */
    .block-picker-header {
      cursor: pointer; user-select: none; display: flex; align-items: center; gap: .4rem;
      font-weight: 600; font-size: .72rem; text-transform: uppercase; letter-spacing: .02em;
      color: #64748b; padding: .35rem 0;
    }
    .block-picker-chevron { transition: transform .15s ease; font-size: .7rem; }
    .block-picker-category.is-collapsed .block-picker-chevron { transform: rotate(-90deg); }
    .block-picker-category.is-collapsed .block-picker-grid { display: none; }
    .block-picker-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; }
    .block-picker-item {
      display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .35rem;
      border: 1px solid var(--bs-border-color, #e2e8f0); border-radius: .5rem; background: #fff;
      padding: .85rem .4rem; min-height: 76px; cursor: pointer; text-align: center;
    }
    .block-picker-item:hover { border-color: var(--bs-primary); box-shadow: 0 1px 6px rgba(79,70,229,.15); }
    .block-picker-item { cursor: grab; }
    .block-picker-item.is-dragging { opacity: .4; }
    .block-picker-item i { font-size: 1.3rem; color: #64748b; }
    .block-picker-item span { font-size: .68rem; color: #475569; line-height: 1.15; }
    .block-picker-item.is-hidden { display: none; }

    /* Container/Grid nested-children mini rail (see _nested_blocks.blade.php). */
    .nested-blocks-list:empty { display: none; }
    .nested-blocks-list > .block-card { margin-left: .25rem; border-left: 2px solid var(--bs-border-color, #e2e8f0); }

    /* Content/Style/Layout tabs inside an open block card — smaller than
       Bootstrap's default nav-tabs, every item bordered (not just the
       active one), inactive items plain white/black, active item filled
       with the brand color instead of Bootstrap's default white-with-
       colored-top-border treatment. */
    .block-settings .nav-tabs { border-bottom: 1px solid var(--bs-border-color, #e2e8f0); }
    .block-settings .nav-tabs .nav-link {
      font-size: .72rem; padding: .3rem .55rem; border: 1px solid var(--bs-border-color, #e2e8f0);
      border-radius: .35rem; color: #000; background: transparent; margin-right: .25rem;
    }
    .block-settings .nav-tabs .nav-link:hover { border-color: var(--bs-primary); }
    .block-settings .nav-tabs .nav-link.active {
      background: var(--bs-primary); border-color: var(--bs-primary); color: #fff;
    }
  </style>

  <div class="editor-shell">
    <div class="editor-topbar d-flex align-items-center justify-content-between px-2 py-2 gap-2 flex-wrap">
      {{-- Section 1: navigation + structural actions --}}
      <div class="d-flex align-items-center gap-1">
        <a href="{{ route('admin.pages.index') }}" class="btn btn-outline-secondary btn-sm" title="{{ __('Back') }}"><i class="bi bi-arrow-left"></i></a>
        <button type="button" class="btn btn-outline-secondary btn-sm js-panel-btn" data-panel="add" title="{{ __('Add Block') }}"><i class="bi bi-plus-lg"></i></button>
        <button type="button" class="btn btn-outline-secondary btn-sm js-panel-btn" data-panel="blocks" title="{{ __('Content Blocks') }}"><i class="bi bi-stack"></i></button>
        <button type="button" class="btn btn-outline-secondary btn-sm js-panel-btn" data-panel="settings" title="{{ __('Page Settings') }}"><i class="bi bi-gear"></i></button>
        <div class="vr mx-1"></div>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-undo" title="{{ __('Undo (Ctrl+Z)') }}" disabled><i class="bi bi-arrow-counterclockwise"></i></button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-redo" title="{{ __('Redo (Ctrl+Y)') }}" disabled><i class="bi bi-arrow-clockwise"></i></button>
        <button type="button" class="btn btn-outline-secondary btn-sm js-panel-btn" data-panel="history" title="{{ __('History') }}"><i class="bi bi-clock-history"></i></button>
      </div>

      {{-- Section 2: page identity + viewport --}}
      <div class="d-flex align-items-center gap-2">
        <span class="fw-semibold small text-truncate" id="topbar-page-name" style="max-width:240px;">{{ $page->title }}</span>
        <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Preview Viewport') }}" id="viewport-toolbar">
          <button type="button" class="btn btn-outline-secondary active" data-viewport="desktop" title="{{ __('Desktop') }}"><i class="bi bi-display"></i></button>
          <button type="button" class="btn btn-outline-secondary" data-viewport="laptop" title="{{ __('Laptop') }}"><i class="bi bi-laptop"></i></button>
          <button type="button" class="btn btn-outline-secondary" data-viewport="tablet" title="{{ __('Tablet') }}"><i class="bi bi-tablet"></i></button>
          <button type="button" class="btn btn-outline-secondary" data-viewport="mobile" title="{{ __('Mobile') }}"><i class="bi bi-phone"></i></button>
        </div>
        <span class="small text-muted" id="preview-status"></span>
      </div>

      {{-- Section 3: preview + publish --}}
      <div class="d-flex align-items-center gap-2">
        @if ($page->status === 'published')
          <a class="btn btn-outline-secondary btn-sm" href="{{ url('/' . $page->slug) }}" target="_blank" title="{{ __('Preview') }}"><i class="bi bi-eye"></i></a>
        @endif
        {{-- Starts disabled — updateSaveButtonState() (see the undo/redo
             history section below) enables it the moment the form actually
             differs from the state the editor loaded with, and disables it
             again if you undo back to that same state. --}}
        <button type="submit" form="page-form" class="btn btn-primary btn-sm" id="btn-save" disabled>
          <i class="bi bi-cloud-upload"></i> {{ $page->status === 'published' ? __('Update') : __('Publish') }}
        </button>
      </div>
    </div>

    <div class="editor-body">
      <div class="editor-sidebar" id="editor-sidebar">
        <div class="sidebar-resize-handle" id="sidebar-resize-handle"></div>

        <form method="POST" action="{{ route('admin.pages.save', $page->id) }}" id="page-form">
          @csrf @method('PUT')

          {{-- Panel: block layers --}}
          <div class="sidebar-panel" data-panel="blocks">
            <div id="main-col">
              <h6 class="small text-muted text-uppercase mb-2">{{ __('Content Blocks') }}</h6>
              <div id="blocks-list">
                @foreach ($view['blocks'] as $i => $b)
                  @include('admin.website.pages._card', ['prefix' => "blocks[$i]", 'type' => $b['type'], 'label' => $blocks[$b['type']] ?? $b['type'], 'data' => $b['data'], 'spec' => $spec, 'style' => $b['style'] ?? [], 'layout' => $b['layout'] ?? [], 'gridTypes' => $gridTypes, 'icon' => $blockIcons[$b['type']] ?? 'bi-square', 'blockIcons' => $blockIcons])
                @endforeach
              </div>
              <p class="text-muted small mb-0" id="blocks-empty" @if(count($view['blocks'])) style="display:none" @endif>{{ __('No Blocks Yet — Add One Above.') }}</p>
            </div>

            <div id="side-col" class="mt-3" @if($view['template'] !== 'sidebar') style="display:none" @endif>
              <h6 class="small text-muted text-uppercase mb-2">{{ __('Sidebar Blocks') }}</h6>
              <div id="sidebar-list">
                @foreach ($view['sidebar'] as $i => $b)
                  @include('admin.website.pages._card', ['prefix' => "sidebar[$i]", 'type' => $b['type'], 'label' => $sidebarBlocks[$b['type']] ?? $b['type'], 'data' => $b['data'], 'spec' => $spec, 'style' => $b['style'] ?? [], 'layout' => $b['layout'] ?? [], 'gridTypes' => $gridTypes, 'icon' => $blockIcons[$b['type']] ?? 'bi-square', 'blockIcons' => $blockIcons])
                @endforeach
              </div>
            </div>
          </div>

          {{-- Panel: add block (sidebar default view) — search + collapsible
               Layout/Basic/Advanced category groups, matching the Elementor-
               style widget picker. See docs/modules/28-elementor-block-editor-plan.md §7d. --}}
          <div class="sidebar-panel active" data-panel="add">
            <div class="input-group input-group-sm mb-3">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" class="form-control" id="block-search" placeholder="{{ __('Search Blocks…') }}">
            </div>

            @foreach ($blockCategories as $catKey => $cat)
              @if (count($cat['types']))
                <div class="block-picker-category mb-3" data-category="{{ $catKey }}">
                  <div class="block-picker-header js-category-toggle">
                    <i class="bi bi-chevron-down block-picker-chevron"></i>
                    <span>{{ $cat['label'] }}</span>
                  </div>
                  <div class="block-picker-grid">
                    @foreach ($cat['types'] as $t)
                      <button type="button" class="block-picker-item js-add-block" draggable="true" data-group="blocks" data-type="{{ $t }}" data-label="{{ \Illuminate\Support\Str::lower($blocks[$t] ?? $t) }}">
                        <i class="bi {{ $blockIcons[$t] ?? 'bi-square' }}"></i>
                        <span>{{ $blocks[$t] ?? $t }}</span>
                      </button>
                    @endforeach
                  </div>
                </div>
              @endif
            @endforeach

            <div class="block-picker-category mb-3" id="add-side-section" data-category="sidebar" @if($view['template'] !== 'sidebar') style="display:none" @endif>
              <div class="block-picker-header js-category-toggle">
                <i class="bi bi-chevron-down block-picker-chevron"></i>
                <span>{{ __('Sidebar Blocks') }}</span>
              </div>
              <div class="block-picker-grid">
                @foreach ($sidebarBlocks as $t => $l)
                  <button type="button" class="block-picker-item js-add-block" draggable="true" data-group="sidebar" data-type="{{ $t }}" data-label="{{ \Illuminate\Support\Str::lower($l) }}">
                    <i class="bi {{ $blockIcons[$t] ?? 'bi-square' }}"></i>
                    <span>{{ $l }}</span>
                  </button>
                @endforeach
              </div>
            </div>

            <p class="text-muted small text-center mb-0 js-no-results" style="display:none">{{ __('No blocks match your search.') }}</p>
          </div>

          {{-- Panel: page settings (Title / Slug / Status / Template) --}}
          <div class="sidebar-panel" data-panel="settings">
            <h6 class="small text-muted text-uppercase mb-3">{{ __('Page Settings') }}</h6>
            <div class="mb-3">
              <label class="form-label small">{{ __('Title') }} <span class="text-danger">*</span></label>
              <input name="title" class="form-control form-control-sm" value="{{ old('title', $page->title) }}" required>
            </div>
            <div class="mb-3">
              <label class="form-label small">{{ __('Slug') }}</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text">/</span>
                <input name="slug" class="form-control" value="{{ old('slug', $page->slug) }}">
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label small">{{ __('Status') }}</label>
              <select name="status" class="form-select form-select-sm">
                <option value="published" @selected($page->status === 'published')>{{ __('Published') }}</option>
                <option value="draft" @selected($page->status === 'draft')>{{ __('Draft') }}</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label small">{{ __('Template') }}</label>
              <select name="template" id="tpl-select" class="form-select form-select-sm">
                <option value="full" @selected($view['template'] === 'full')>{{ __('Full Width') }}</option>
                <option value="sidebar" @selected($view['template'] === 'sidebar')>{{ __('With Sidebar') }}</option>
              </select>
            </div>
          </div>
        </form>

        {{-- Panel: revision history — outside #page-form (has its own restore
             forms; a <form> cannot nest inside another <form>). Uses
             $page->layouts, eager-loaded with createdBy by PageController::edit(). --}}
        <div class="sidebar-panel" data-panel="history">
          <h6 class="small text-muted text-uppercase mb-3">{{ __('History') }}</h6>
          <div class="list-group list-group-flush small">
            @forelse ($page->layouts as $rev)
              <div class="list-group-item px-0 py-2">
                <div class="fw-semibold">{{ $rev->created_at?->format('M j, Y g:i A') }}</div>
                <div class="text-muted mb-1">{{ $rev->createdBy?->name ?? __('Unknown') }}</div>
                <div class="mb-1">
                  @if($loop->first)<span class="badge bg-secondary">{{ __('Latest') }}</span>@endif
                  @if($rev->is_published)<span class="badge bg-success">{{ __('Published') }}</span>@endif
                </div>
                @unless($loop->first)
                  <form method="POST" action="{{ route('admin.pages.restore', [$page->id, $rev->id]) }}" onsubmit="return confirm('{{ __('Restore this revision as a new draft?') }}')">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100">{{ __('Restore') }}</button>
                  </form>
                @endunless
              </div>
            @empty
              <p class="text-muted small mb-0">{{ __('No revisions yet.') }}</p>
            @endforelse
          </div>
        </div>
      </div>

      {{-- Canvas — same render pipeline as the public site, fed from the
           form's current (unsaved) values. See
           docs/modules/28-elementor-block-editor-plan.md. --}}
      <div class="editor-canvas" id="preview-viewport-wrap">
        <iframe id="preview-frame" title="{{ __('Live Preview') }}" sandbox="allow-same-origin allow-scripts" style="height:100%;border:0;display:block;"></iframe>
      </div>
    </div>
  </div>

  {{-- Hidden block templates for the "Add" buttons (prefix placeholder __I__) --}}
  @foreach ($blocks as $t => $l)
    <template id="tpl-blocks-{{ $t }}">@include('admin.website.pages._card', ['prefix' => 'blocks[__I__]', 'type' => $t, 'label' => $l, 'data' => [], 'spec' => $spec, 'style' => [], 'layout' => [], 'gridTypes' => $gridTypes, 'icon' => $blockIcons[$t] ?? 'bi-square', 'blockIcons' => $blockIcons])</template>
  @endforeach
  @foreach ($sidebarBlocks as $t => $l)
    <template id="tpl-sidebar-{{ $t }}">@include('admin.website.pages._card', ['prefix' => 'sidebar[__I__]', 'type' => $t, 'label' => $l, 'data' => [], 'spec' => $spec, 'style' => [], 'layout' => [], 'gridTypes' => $gridTypes, 'icon' => $blockIcons[$t] ?? 'bi-square', 'blockIcons' => $blockIcons])</template>
  @endforeach

  {{-- Hidden templates for a Container/Grid's own nested children — every
       block type, INCLUDING container/grid (nesting is recursive now, see
       §7g in docs/modules/28-elementor-block-editor-plan.md) — the
       "how deep is too deep" cap is enforced by _nested_blocks.blade.php
       simply not offering container/grid in its "Add child" dropdown past
       PageRenderService::MAX_NESTING_DEPTH, not by omitting the template
       here. Uses a __PREFIX__ token instead of a literal "blocks"/"sidebar"
       root: addChildBlock() substitutes it with the specific container's
       own data-prefix at insert time, since a child's real prefix
       ("blocks[2][data][blocks][0]") depends on which container it's being
       added to, not a fixed top-level list. --}}
  @foreach (\App\Modules\Website\Services\PageRenderService::BLOCKS as $t => $l)
    <template id="tpl-child-{{ $t }}">@include('admin.website.pages._card', ['prefix' => '__PREFIX__[__I__]', 'type' => $t, 'label' => $l, 'data' => [], 'spec' => $spec, 'style' => [], 'layout' => [], 'gridTypes' => $gridTypes, 'icon' => $blockIcons[$t] ?? 'bi-square', 'blockIcons' => $blockIcons])</template>
  @endforeach

  {{-- Media Library modal — opened by any field's "Browse" button (see
       _fields.blade.php's 'media' input, openMediaPicker() below). One
       shared modal/grid for the whole editor; mediaPickerTargetInput tracks
       which text field a click on a thumbnail should fill. --}}
  <div class="modal fade" id="media-picker-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h6 class="modal-title mb-0">{{ __('Media Library') }}</h6>
          <button type="button" class="btn btn-primary btn-sm ms-auto me-2" onclick="document.getElementById('media-upload-input').click()">
            <i class="bi bi-upload"></i> {{ __('Upload') }}
          </button>
          <input type="file" id="media-upload-input" class="d-none" accept="image/*,video/mp4,video/webm">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
        </div>
        <div class="modal-body">
          <div id="media-picker-status" class="small text-muted mb-2"></div>
          <div id="media-picker-grid" class="row row-cols-3 row-cols-md-4 g-2"></div>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
      // ── Sidebar panel switching ─────────────────────────────────────────
      // "add" (Add Block) is the sidebar's default/resting view, matching
      // Elementor's default left panel. "blocks" (the layers list),
      // "settings", and "history" are all considered a temporary "active
      // box" — clicking outside the sidebar or pressing Escape collapses
      // back to "add" and closes any open block-settings card.
      var DEFAULT_PANEL = 'add';
      function showPanel(name) {
        document.querySelectorAll('.sidebar-panel').forEach(function (p) {
          p.classList.toggle('active', p.dataset.panel === name);
        });
        document.querySelectorAll('.js-panel-btn').forEach(function (b) {
          b.classList.toggle('active', b.dataset.panel === name);
        });
      }
      function resetSidebarToDefault() {
        showPanel(DEFAULT_PANEL);
        var blocksList = document.getElementById('blocks-list');
        var sidebarList = document.getElementById('sidebar-list');
        if (blocksList) closeBlockList(blocksList);
        if (sidebarList) closeBlockList(sidebarList);
      }
      document.querySelectorAll('.js-panel-btn').forEach(function (b) {
        b.addEventListener('click', function (e) {
          e.stopPropagation(); // don't let the click-outside handler below immediately undo this
          showPanel(b.dataset.panel);
        });
      });
      // Click anywhere outside the sidebar (canvas background, topbar,
      // wherever) collapses it back to the default Add Block panel. Set by
      // the resize-drag handler below to swallow the single spurious click
      // a mouseup outside the sidebar would otherwise fire after a resize.
      var sidebarResizeJustEnded = false;
      document.addEventListener('click', function (e) {
        if (sidebarResizeJustEnded) { sidebarResizeJustEnded = false; return; }
        if (e.target.closest('#editor-sidebar')) return;
        resetSidebarToDefault();
      });
      // Escape always returns the sidebar to its default state, from
      // anywhere in the editor chrome (not inside the preview iframe, which
      // has its own Escape handling for its right-click menu).
      document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        resetSidebarToDefault();
        if (document.activeElement && document.activeElement.blur) document.activeElement.blur();
      });

      // ── Resizable sidebar (12.5vw floor, 25vw ceiling) ───────────────────
      // Width is stored as a vw PERCENTAGE (not px) under one fixed
      // localStorage key shared by every page's editor — so the split you
      // leave on one page's editor is restored the next time you open ANY
      // page for editing, not just this one.
      (function () {
        var sidebar = document.getElementById('editor-sidebar');
        var handle = document.getElementById('sidebar-resize-handle');
        if (!sidebar || !handle) return;
        var MIN_PCT = 12.5, MAX_PCT = 25;
        var STORAGE_KEY = 'website-editor-sidebar-width-pct';
        function clampPct(pct) { return Math.min(MAX_PCT, Math.max(MIN_PCT, pct)); }
        var storedPct = parseFloat(localStorage.getItem(STORAGE_KEY));
        if (!isNaN(storedPct)) sidebar.style.width = clampPct(storedPct) + 'vw';

        var dragging = false;
        handle.addEventListener('mousedown', function (e) {
          dragging = true;
          handle.classList.add('is-dragging');
          document.body.style.cursor = 'col-resize';
          e.preventDefault();
        });
        document.addEventListener('mousemove', function (e) {
          if (!dragging) return;
          var pct = clampPct((e.clientX / window.innerWidth) * 100);
          sidebar.style.width = pct + 'vw';
        });
        document.addEventListener('mouseup', function () {
          if (!dragging) return;
          dragging = false;
          handle.classList.remove('is-dragging');
          document.body.style.cursor = '';
          // The mouseup can land outside #editor-sidebar (that's the whole
          // point of dragging the divider) — without this, the click-outside
          // handler above would immediately reset the sidebar right after
          // every resize.
          sidebarResizeJustEnded = true;
          var pct = parseFloat(sidebar.style.width);
          if (!isNaN(pct)) {
            try { localStorage.setItem(STORAGE_KEY, pct); } catch (err) {}
          }
        });
      })();

      var blockIdx = 1000;
      // Shared by the top-level "no blocks yet" message and a container/
      // grid's own "no children yet" message — toggled after any add/remove
      // so the message reappears if the last block/child is ever removed
      // (a gap the original single-purpose inline toggle had for top-level
      // blocks too, fixed here for both at once).
      function updateEmptyState(list) {
        if (!list) return;
        var isEmpty = list.children.length === 0;
        if (list.id === 'blocks-list') {
          var empty = document.getElementById('blocks-empty');
          if (empty) empty.style.display = isEmpty ? '' : 'none';
        } else if (list.classList.contains('nested-blocks-list')) {
          var wrap = list.closest('.nested-blocks-wrap');
          var empty = wrap && wrap.querySelector('.js-nested-empty');
          if (empty) empty.style.display = isEmpty ? '' : 'none';
        }
      }
      // Sortable (drag-reorder via the grip handle) needs to be initialized
      // per list element, including a container/grid's own nested-blocks-list
      // — called after any structural change that could introduce a new one
      // (idempotent via data-sortable-init, so calling it liberally is cheap).
      function initNestedSortables() {
        if (!window.Sortable) return;
        document.querySelectorAll('.nested-blocks-list').forEach(function (list) {
          if (list.dataset.sortableInit) return;
          list.dataset.sortableInit = 'true';
          new Sortable(list, {
            handle: '.js-drag-handle',
            animation: 150,
            ghostClass: 'opacity-50',
            onEnd: function () { schedulePreview(); pushHistory(); },
          });
        });
      }
      // Inserts a template's HTML either at the end of `list` or right
      // before `refNode` (an existing child of `list`) — shared by
      // addBlock() (always appends) and addBlockAt() (canvas drag-drop,
      // arbitrary position) so there's one insertion implementation.
      function insertBlockHtml(list, html, refNode) {
        if (refNode) {
          refNode.insertAdjacentHTML('beforebegin', html);
          return refNode.previousElementSibling;
        }
        list.insertAdjacentHTML('beforeend', html);
        return list.lastElementChild;
      }
      // Everything a freshly inserted top-level block needs regardless of
      // where it landed — shared by addBlock() and addBlockAt().
      function finishBlockInsert(list, card) {
        if (!card) return;
        updateEmptyState(list);
        initQuillEditors();
        initNestedSortables();
        // A style may already be copied from an earlier block — the new
        // block's Paste Style button starts disabled server-side, enable it too.
        if (copiedStyle) { document.querySelectorAll('.js-paste-style').forEach(function (b) { b.disabled = false; }); }
        // Switch to the block layers panel and open the newly added block's
        // settings immediately, like Elementor does when you drop a new
        // widget — you're almost always about to configure it right away.
        showPanel('blocks');
        openBlockCard(card);
        applyFieldDependencies(card);
        schedulePreview();
        pushHistory();
      }
      function addBlock(group, type) {
        var tpl = document.getElementById('tpl-' + group + '-' + type);
        if (!tpl) return;
        var html = tpl.innerHTML.split('__I__').join(blockIdx++);
        var list = document.getElementById(group + '-list');
        finishBlockInsert(list, insertBlockHtml(list, html, null));
      }
      // ── Path resolution (arbitrary nesting depth — see §7g in
      // docs/modules/28-elementor-block-editor-plan.md) ─────────────────────
      // A block's path is the same list of indices its rendered element
      // carries as data-block-path in the preview iframe ("2" for the 3rd
      // top-level block, "2,0" for its 1st child, …). These walk the
      // EDITOR's own live DOM — never cached, always recomputed — exactly
      // like the pre-existing top-level `list.children[index]` lookups this
      // whole system has always relied on, just generalized to descend
      // through nested containers' own `.nested-blocks-list` rails too.
      function resolveListByPath(group, parentPath) {
        var list = document.getElementById(group === 'sidebar' ? 'sidebar-list' : 'blocks-list');
        for (var i = 0; i < parentPath.length && list; i++) {
          var card = list.children[parentPath[i]];
          // A card's OWN nested-blocks-list is always the first one found by
          // subtree order (its wrap comes before any deeper descendant's —
          // see the comment in _nested_blocks.blade.php), so a plain
          // (non-:scope) querySelector is safe here.
          list = card ? card.querySelector('.nested-blocks-wrap > .nested-blocks-list') : null;
        }
        return list || null;
      }
      function resolveCardByPath(group, path) {
        if (!path || !path.length) return null;
        var parentList = resolveListByPath(group, path.slice(0, -1));
        return parentList ? (parentList.children[path[path.length - 1]] || null) : null;
      }
      // The inverse: given a card already in hand (e.g. from a form 'input'
      // event target), walk UP to compute its own path.
      function computeCardPath(card, group) {
        var path = [];
        var node = card;
        while (node) {
          var list = node.parentElement;
          if (!list) return null;
          var idx = Array.prototype.indexOf.call(list.children, node);
          if (idx < 0) return null;
          path.unshift(idx);
          if (list.id === 'blocks-list' || list.id === 'sidebar-list') {
            if ((group === 'sidebar') !== (list.id === 'sidebar-list')) return null;
            return path;
          }
          // Not a root list — must be a nested container's children rail;
          // step up to the .block-card that owns it. (.closest() only ever
          // finds an ANCESTOR, so this can't accidentally jump to a child
          // card instead of the true parent.)
          node = list.closest('.block-card');
          if (!node) return null;
        }
        return null;
      }
      function pathWithin(path, ancestorPath) {
        if (path.length < ancestorPath.length) return false;
        for (var i = 0; i < ancestorPath.length; i++) if (path[i] !== ancestorPath[i]) return false;
        return true;
      }
      // Dragging a block-type box from the Add Block panel and dropping it
      // at a specific spot on the canvas (see public/layout.blade.php's
      // 'add-block-at' postMessage) — toParentPath/toIndex describe a
      // position among the DESTINATION list's children AS THEY WERE the
      // last time the preview rendered, which lines up with that list's
      // current DOM order under the same invariant every other
      // canvas-driven action (move-block, select-block) relies on.
      // toParentPath non-empty means "drop as a new child inside that
      // container" — uses the tpl-child-{type} template (a __PREFIX__
      // token substituted from the destination list's own data-prefix),
      // exactly like _nested_blocks.blade.php's own "Add" button
      // (addChildBlock()) does; empty path means the ordinary top-level
      // tpl-{group}-{type} template.
      function addBlockAt(group, type, toParentPath, toIndex) {
        var list = resolveListByPath(group, toParentPath || []);
        if (!list) return;
        var html;
        if (toParentPath && toParentPath.length) {
          var tpl = document.getElementById('tpl-child-' + type);
          if (!tpl) return;
          var prefixRoot = list.dataset.prefix + '[data][blocks]';
          html = tpl.innerHTML.split('__PREFIX__').join(prefixRoot).split('__I__').join(blockIdx++);
        } else {
          var tpl = document.getElementById('tpl-' + group + '-' + type);
          if (!tpl) return;
          html = tpl.innerHTML.split('__I__').join(blockIdx++);
        }
        var refNode = (toIndex !== null && toIndex !== undefined) ? (list.children[toIndex] || null) : null;
        finishBlockInsert(list, insertBlockHtml(list, html, refNode));
      }
      // A container/grid's own "Add" control (see _nested_blocks.blade.php)
      // — same idea as addBlock() above, but the child's prefix is built
      // from the specific container instance's own data-prefix rather than
      // a fixed top-level "blocks"/"sidebar" root (see the hidden
      // tpl-child-{type} templates' __PREFIX__ token, near the bottom of
      // this file).
      function addChildBlock(list, type) {
        var tpl = document.getElementById('tpl-child-' + type);
        if (!tpl || !list) return;
        var prefixRoot = list.dataset.prefix + '[data][blocks]';
        var html = tpl.innerHTML.split('__PREFIX__').join(prefixRoot).split('__I__').join(blockIdx++);
        list.insertAdjacentHTML('beforeend', html);
        updateEmptyState(list);
        initQuillEditors();
        initNestedSortables();
        if (copiedStyle) { document.querySelectorAll('.js-paste-style').forEach(function (b) { b.disabled = false; }); }
        openBlockCard(list.lastElementChild);
        applyFieldDependencies(list.lastElementChild);
        schedulePreview();
        pushHistory();
      }
      document.addEventListener('click', function (e) {
        var addBtn = e.target.closest('.js-add-block');
        if (addBtn) { addBlock(addBtn.dataset.group, addBtn.dataset.type); return; }
        var addChildBtn = e.target.closest('.js-nested-add-btn');
        if (addChildBtn) {
          var wrap = addChildBtn.closest('.nested-blocks-wrap');
          var select = wrap && wrap.querySelector('.js-nested-type');
          var nestedList = wrap && wrap.querySelector('.nested-blocks-list');
          if (select && nestedList) addChildBlock(nestedList, select.value);
        }
      });

      // Drag a block-type box from the Add Block panel straight onto the
      // canvas — the counterpart to public/layout.blade.php's dragover/drop
      // handling. This side just publishes what's being dragged
      // (dataTransfer can't be read by the iframe until drop, only its
      // .types checked during dragover — see that file's comment); the
      // iframe computes the drop position and posts back an 'add-block-at'
      // message (handled below, near the other canvas-bridge messages).
      document.addEventListener('dragstart', function (e) {
        var item = e.target.closest('.js-add-block');
        if (!item) return;
        var payload = JSON.stringify({ group: item.dataset.group, type: item.dataset.type });
        item.classList.add('is-dragging');
        if (e.dataTransfer) {
          e.dataTransfer.effectAllowed = 'copy';
          try {
            e.dataTransfer.setData('application/x-block-type', payload);
            e.dataTransfer.setData('text/plain', payload);
          } catch (err) {}
        }
      });
      document.addEventListener('dragend', function (e) {
        var item = e.target.closest('.js-add-block');
        if (item) item.classList.remove('is-dragging');
      });

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

      // ── Conditional field visibility ─────────────────────────────────────
      // A field with a `depends_on` in its $spec entry (see _fields.blade.php)
      // gets a data-depends-on/data-depends-values wrapper; show/hide it
      // based on the CURRENT value of the named control within the same
      // card. Generic — works for any block type, not just video (the first
      // user of it: External URL / Video File URL depending on Source).
      // Limitation: looks up the control by [name$="[data][KEY]"], which for
      // a checkbox/switch field matches its hidden(0) input first, not the
      // checkbox — a boolean field as the *depended-on* control isn't
      // supported by this lookup. Not needed by anything today (Source is a
      // <select>), just noting it for whoever adds the next depends_on.
      function applyFieldDependencies(card) {
        if (!card) return;
        card.querySelectorAll('[data-depends-on]').forEach(function (wrap) {
          var depKey = wrap.dataset.dependsOn;
          var allowed = (wrap.dataset.dependsValues || '').split(',');
          var control = card.querySelector('[name$="[data][' + depKey + ']"]');
          var current = control ? control.value : '';
          wrap.style.display = allowed.indexOf(current) !== -1 ? '' : 'none';
        });
      }
      function applyAllFieldDependencies() {
        document.querySelectorAll('.block-card').forEach(applyFieldDependencies);
      }
      document.addEventListener('change', function (e) {
        if (e.target.closest('.block-settings')) applyFieldDependencies(e.target.closest('.block-card'));
      });
      document.addEventListener('DOMContentLoaded', applyAllFieldDependencies);
      if (document.readyState !== 'loading') applyAllFieldDependencies();

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
      // Any container/grid blocks already on the page (existing content,
      // server-rendered) get their own nested-blocks-list initialized too.
      initNestedSortables();

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
      // Shared by both the sidebar's Copy/Paste Style buttons and the
      // preview canvas's right-click context menu (see the message handler
      // below) — one implementation, two entry points.
      function copyStyleFromCard(card) {
        copiedStyle = styleFieldsIn(card);
        document.querySelectorAll('.js-paste-style').forEach(function (b) { b.disabled = false; });
      }
      function pasteStyleToCard(card) {
        if (!copiedStyle) return;
        Object.keys(copiedStyle).forEach(function (key) {
          var input = card.querySelector('[name$="[style][' + key + ']"]');
          if (!input) return;
          input.value = copiedStyle[key];
          // Dispatched (not assigned) so the delegated swatch-sync and
          // live-preview/history listeners already on these fields pick the
          // change up exactly as if the user had typed it — see
          // handleFormChange() in the live-preview IIFE below.
          input.dispatchEvent(new Event('input', { bubbles: true }));
          input.dispatchEvent(new Event('change', { bubbles: true }));
        });
        pushHistory();
      }
      function removeCard(card) {
        if (!card) return;
        var list = card.parentElement;
        card.remove();
        updateEmptyState(list);
        schedulePreview();
        pushHistory();
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
      // The very first snapshot pushHistory() ever takes (the page as the
      // editor loaded it) — cached separately from history_[0] because
      // HISTORY_LIMIT eventually shifts history_[0] out on a long editing
      // session, but "the state Update should be disabled at" never changes.
      var initialSnapshotJson = null;
      function updateSaveButtonState() {
        var saveBtn = document.getElementById('btn-save');
        if (!saveBtn || initialSnapshotJson === null) return;
        saveBtn.disabled = JSON.stringify(snapshotState()) === initialSnapshotJson;
      }

      // Structure-aware capture/restore (§7g in
      // docs/modules/28-elementor-block-editor-plan.md): a card's OWN fields
      // are isolated from a nested child's by checking which .block-card
      // el.closest('.block-card') resolves to — plain '.block-settings
      // [name]' would otherwise sweep up every descendant's fields too
      // (nested cards live inside their parent's DOM subtree), flattening
      // them into one undifferentiated positional array with no record of
      // how many children existed or where the boundaries between them
      // were. That flat/positional approach is what let undo/redo silently
      // DROP a container's children on every restore before this fix — a
      // fresh tpl-{group}-{type} clone starts with zero nested cards, so
      // there was nothing for those extra captured values to slot into.
      function cardOwnFields(card) {
        return Array.prototype.filter.call(card.querySelectorAll('.block-settings [name]'), function (el) {
          return el.closest('.block-card') === card;
        });
      }
      function captureCardOwnFields(card) {
        return cardOwnFields(card).map(function (el) {
          return (el.type === 'checkbox' || el.type === 'radio') ? { checked: el.checked } : { value: el.value };
        });
      }
      function applyCardOwnFields(card, captured) {
        var els = cardOwnFields(card);
        captured.forEach(function (c, i) {
          var el = els[i];
          if (!el) return;
          if (el.type === 'checkbox' || el.type === 'radio') { el.checked = !!c.checked; } else { el.value = c.value; }
        });
      }
      // A card's own nested-blocks-list, if it has one (see the identical
      // "first in document order = the direct one" reasoning in
      // resolveListByPath() above) — null for a leaf block.
      function ownNestedList(card) {
        return card.querySelector('.nested-blocks-wrap > .nested-blocks-list');
      }
      function captureCard(card) {
        var typeInput = cardOwnFields(card).filter(function (el) { return /\[type\]$/.test(el.name); })[0];
        var nestedList = ownNestedList(card);
        return {
          type: typeInput ? typeInput.value : '',
          fields: captureCardOwnFields(card),
          children: nestedList ? Array.prototype.map.call(nestedList.children, captureCard) : null,
        };
      }
      function captureList(listId) {
        var list = document.getElementById(listId);
        return Array.prototype.map.call(list.children, captureCard);
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
      // Rebuilds one card from a captured node — top-level via the ordinary
      // tpl-{group}-{type} template, a nested child via tpl-child-{type}
      // (same __PREFIX__ substitution addChildBlock() uses) — then
      // recursively rebuilds any children it had, so a container's whole
      // subtree round-trips exactly, regardless of how many children it had
      // or how deep the nesting went.
      function restoreCardInto(list, group, snap, isChild) {
        var tpl = document.getElementById(isChild ? 'tpl-child-' + snap.type : 'tpl-' + group + '-' + snap.type);
        if (!tpl) return null;
        var html;
        if (isChild) {
          var prefixRoot = list.dataset.prefix + '[data][blocks]';
          html = tpl.innerHTML.split('__PREFIX__').join(prefixRoot).split('__I__').join(blockIdx++);
        } else {
          html = tpl.innerHTML.split('__I__').join(blockIdx++);
        }
        list.insertAdjacentHTML('beforeend', html);
        var card = list.lastElementChild;
        applyCardOwnFields(card, snap.fields);
        if (snap.children) {
          var nestedList = ownNestedList(card);
          if (nestedList) {
            snap.children.forEach(function (childSnap) { restoreCardInto(nestedList, group, childSnap, true); });
            updateEmptyState(nestedList);
          }
        }
        return card;
      }
      function restoreList(listId, group, snapshotBlocks) {
        var list = document.getElementById(listId);
        list.innerHTML = '';
        snapshotBlocks.forEach(function (b) { restoreCardInto(list, group, b, false); });
      }
      function restoreSnapshot(snap) {
        document.querySelector('[name="title"]').value = snap.title;
        document.querySelector('[name="slug"]').value = snap.slug;
        document.querySelector('[name="status"]').value = snap.status;
        document.getElementById('tpl-select').value = snap.template;
        document.getElementById('side-col').style.display = snap.template === 'sidebar' ? '' : 'none';
        var addSide = document.getElementById('add-side-section');
        if (addSide) addSide.style.display = snap.template === 'sidebar' ? '' : 'none';
        restoreList('blocks-list', 'blocks', snap.blocks);
        restoreList('sidebar-list', 'sidebar', snap.sidebar);
        var empty = document.getElementById('blocks-empty');
        if (empty) empty.style.display = snap.blocks.length ? 'none' : '';
        var nameEl = document.getElementById('topbar-page-name');
        if (nameEl) nameEl.textContent = snap.title || @json(__('Untitled'));
        initQuillEditors();
        initNestedSortables();
        applyAllFieldDependencies();
        schedulePreview();
        updateUndoRedoButtons();
        updateSaveButtonState();
      }
      function pushHistory() {
        var snap = snapshotState();
        history_ = history_.slice(0, historyIndex + 1);
        history_.push(snap);
        if (history_.length > HISTORY_LIMIT) history_.shift();
        historyIndex = history_.length - 1;
        if (initialSnapshotJson === null) initialSnapshotJson = JSON.stringify(snap);
        updateUndoRedoButtons();
        updateSaveButtonState();
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
        if (rm) { removeCard(rm.closest('.block-card')); return; }
        if (toggle) { toggleBlockCard(toggle.closest('.block-card')); return; }
        if (copyStyle) {
          copyStyleFromCard(copyStyle.closest('.block-card'));
          copyStyle.classList.replace('btn-outline-secondary', 'btn-success');
          setTimeout(function () { copyStyle.classList.replace('btn-success', 'btn-outline-secondary'); }, 700);
          return;
        }
        if (pasteStyle) {
          if (!copiedStyle) return;
          pasteStyleToCard(pasteStyle.closest('.block-card'));
          pasteStyle.classList.replace('btn-outline-secondary', 'btn-success');
          setTimeout(function () { pasteStyle.classList.replace('btn-success', 'btn-outline-secondary'); }, 700);
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
        var addSide = document.getElementById('add-side-section');
        if (addSide) addSide.style.display = sidebar ? '' : 'none';
        schedulePreview();
        pushHistory();
      });

      // Topbar page-name label — mirrors the Title field live.
      document.querySelector('[name="title"]').addEventListener('input', function () {
        var el = document.getElementById('topbar-page-name');
        if (el) el.textContent = this.value || @json(__('Untitled'));
      });

      // Responsive viewport toolbar — resizes the preview iframe only, no re-render needed.
      document.getElementById('viewport-toolbar').addEventListener('click', function (e) {
        var btn = e.target.closest('[data-viewport]');
        if (!btn) return;
        this.querySelectorAll('[data-viewport]').forEach(function (b) { b.classList.toggle('active', b === btn); });
        var canvas = document.getElementById('preview-viewport-wrap');
        canvas.classList.remove('vp-laptop', 'vp-tablet', 'vp-mobile');
        if (btn.dataset.viewport !== 'desktop') canvas.classList.add('vp-' + btn.dataset.viewport);
      });

      // ── Add Block panel: search + collapsible categories ────────────────
      var blockSearch = document.getElementById('block-search');
      if (blockSearch) {
        blockSearch.addEventListener('input', function () {
          var q = this.value.trim().toLowerCase();
          var anyVisible = false;
          document.querySelectorAll('.block-picker-category').forEach(function (cat) {
            var catMatch = false;
            cat.querySelectorAll('.block-picker-item').forEach(function (item) {
              var match = !q || (item.dataset.label || '').indexOf(q) !== -1;
              item.classList.toggle('is-hidden', !match);
              if (match) catMatch = true;
            });
            if (catMatch) anyVisible = true;
            // Searching always shows a matching category's contents, ignoring
            // any manual collapse; clearing the search restores normal (open) display.
            cat.classList.remove('is-collapsed');
            if (cat.id === 'add-side-section') {
              // The Sidebar Blocks category has its own template-driven
              // visibility (tpl-select change handler / restoreSnapshot())
              // that this must not fight with once the search is cleared.
              cat.style.display = q ? (catMatch ? '' : 'none') : (document.getElementById('tpl-select').value === 'sidebar' ? '' : 'none');
            } else {
              cat.style.display = (q && !catMatch) ? 'none' : '';
            }
          });
          var noResults = document.querySelector('.js-no-results');
          if (noResults) noResults.style.display = (q && !anyVisible) ? '' : 'none';
        });
      }
      document.querySelectorAll('.js-category-toggle').forEach(function (h) {
        h.addEventListener('click', function () {
          h.closest('.block-picker-category').classList.toggle('is-collapsed');
        });
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
      // source (BSD-3), loaded globally from CDN in layouts/admin-fullscreen.blade.php,
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
            updateSaveButtonState();
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
            // Strip this block's own addressing prefix down to a bare
            // "block" root, regardless of nesting depth — a top-level
            // field is named "blocks[2][data][text]", a child nested one
            // level in is "blocks[2][data][blocks][0][data][text]", one
            // level deeper still repeats the "[data][blocks][N]" segment
            // again, and so on. previewBlock() only ever expects a single
            // block's own field names ("block[data][text]"), never a
            // nested wrapper.
            fd.append(el.name.replace(/^(?:blocks|sidebar)\[\d+\](?:\[data\]\[blocks\]\[\d+\])*/, 'block'), el.value);
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
          var path = computeCardPath(card, group);
          var target = path ? frameDoc.querySelector('[data-block-group="' + group + '"][data-block-path="' + path.join(',') + '"]') : null;
          if (!target) { schedulePreview(); return; } // never rendered there yet — do a full reload instead

          var fd = blockFormData(card);
          fd.append('group', group);
          // A nested child (path.length > 1) is ALWAYS rendered inside its
          // parent's own wrapper (see the container/grid @case in
          // public/blocks/render.blade.php, which always passes
          // contained=>true to its children) regardless of the page
          // template; only a top-level block's containment depends on the
          // sidebar template's narrower column.
          fd.append('contained', (path.length > 1 || (group === 'blocks' && document.getElementById('tpl-select').value === 'sidebar')) ? '1' : '0');
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
            next.setAttribute('data-block-path', path.join(','));
            next.setAttribute('data-block-group', group);
            // previewBlock() doesn't know this block's own type isn't
            // changing (it just re-renders the fields it was given) — carry
            // it over from the element being replaced so drag-into-container
            // detection (data-block-type) keeps working after a field edit.
            var oldType = target.getAttribute('data-block-type');
            if (oldType) next.setAttribute('data-block-type', oldType);
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
          updateSaveButtonState();
        }
        form.addEventListener('input', handleFormChange);
        form.addEventListener('change', handleFormChange);

        document.addEventListener('DOMContentLoaded', schedulePreview);
        if (document.readyState !== 'loading') schedulePreview();
      })();

      // ── Preview canvas bridge ────────────────────────────────────────────
      // The preview iframe (public/layout.blade.php) posts messages for
      // everything that happens directly on the canvas: click-to-select
      // (including nested children — see §7g), clicking the background
      // (deselect), drag-reorder/drag-into-a-container, and the right-click
      // Copy/Paste/Delete menu. Every block is addressed by PATH (a list of
      // indices from the root, e.g. [2,0,1]) rather than a single flat
      // index, resolved against this form's current DOM order via
      // resolveCardByPath()/resolveListByPath() above — which lines up
      // exactly with what the preview just rendered, the same invariant
      // every canvas-driven action has always relied on.
      window.addEventListener('message', function (e) {
        // Verify by sender identity (e.source), not e.origin: the preview
        // iframe is loaded via .srcdoc, whose origin serializes as the
        // literal string "null" (a browser quirk), so an origin-string
        // comparison would always fail here. (Looked up fresh rather than
        // reusing the `frame` var from the live-preview IIFE above, which is
        // out of scope here.)
        var previewFrame = document.getElementById('preview-frame');
        if (!previewFrame || e.source !== previewFrame.contentWindow) return;
        var msg = e.data;
        if (!msg || msg.source !== 'page-preview') return;

        if (msg.type === 'select-block') {
          var card = resolveCardByPath(msg.group, msg.path);
          if (card) {
            showPanel('blocks');
            openBlockCard(card);
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
          return;
        }

        if (msg.type === 'deselect') {
          // Clicked the canvas background — same as any other click outside
          // the sidebar's active box.
          resetSidebarToDefault();
          return;
        }

        if (msg.type === 'move-block') {
          // Covers plain sibling reordering, dropping a block INSIDE a
          // container/grid, and pulling a nested child back out — all
          // "move the block at fromPath to toIndex within toParentPath's
          // list" (see the matching comment in public/layout.blade.php).
          if (!Array.isArray(msg.fromPath) || !Array.isArray(msg.toParentPath)) return;
          if (pathWithin(msg.toParentPath, msg.fromPath)) return; // can't nest a block inside itself
          var card = resolveCardByPath(msg.group, msg.fromPath);
          var destList = resolveListByPath(msg.group, msg.toParentPath);
          if (!card || !destList) return;
          var refNode = (msg.toIndex !== null && msg.toIndex !== undefined) ? (destList.children[msg.toIndex] || null) : null;
          if (refNode === card) return; // dropped on itself — no-op
          var srcList = card.parentElement;
          destList.insertBefore(card, refNode);
          updateEmptyState(srcList);
          updateEmptyState(destList);
          initNestedSortables();
          schedulePreview();
          pushHistory();
          return;
        }

        if (msg.type === 'add-block-at') {
          // A block-type box was dragged from the Add Block panel and
          // dropped on the canvas — see addBlockAt() and the dragstart/drop
          // handlers above/in public/layout.blade.php.
          if (!msg.group || !msg.blockType) return;
          addBlockAt(msg.group, msg.blockType, msg.toParentPath || [], msg.toIndex);
          return;
        }

        if (msg.type === 'context-action') {
          var card = resolveCardByPath(msg.group, msg.path);
          if (!card) return;
          if (msg.action === 'copy') { copyStyleFromCard(card); }
          else if (msg.action === 'paste') { pasteStyleToCard(card); }
          else if (msg.action === 'delete') { removeCard(card); }
          return;
        }
      });

      // ── Media Library (upload + picker) ─────────────────────────────────
      // Shared modal opened from any 'media' field's "Browse" button
      // (_fields.blade.php). One flow: list -> click Upload -> re-list ->
      // click a thumbnail to fill whichever input opened the modal.
      (function () {
        var modalEl = document.getElementById('media-picker-modal');
        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        var grid = document.getElementById('media-picker-grid');
        var status = document.getElementById('media-picker-status');
        var uploadInput = document.getElementById('media-upload-input');
        var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        var targetInput = null;
        var items = null; // cached list — refetched lazily, not on every open

        window.openMediaPicker = function (btn) {
          // The Browse button sits right after the text input inside the
          // same .input-group (_fields.blade.php's 'media' markup).
          targetInput = btn.closest('.input-group').querySelector('.media-field-input');
          modal.show();
          if (!items) loadMediaLibrary();
        };

        function loadMediaLibrary() {
          status.textContent = @json(__('Loading…'));
          fetch(@json(route('admin.media.index')), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (res) { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
            .then(function (data) { items = data; renderGrid(); })
            .catch(function () { status.textContent = @json(__('Failed to load media library.')); });
        }

        function renderGrid() {
          grid.innerHTML = '';
          status.textContent = items.length ? '' : @json(__('No media uploaded yet.'));
          items.forEach(function (item) {
            var col = document.createElement('div');
            col.className = 'col';
            var thumbInner = item.is_image
              ? '<img src="' + item.url + '" class="w-100" style="height:90px;object-fit:cover;" alt="">'
              : '<div class="w-100 d-flex align-items-center justify-content-center bg-body-secondary" style="height:90px;"><i class="bi bi-file-play fs-3"></i></div>';
            col.innerHTML =
              '<div class="border rounded position-relative media-picker-item" style="cursor:pointer;">' +
                thumbInner +
                '<button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 py-0 px-1 media-picker-delete" title="' + @json(__('Delete')) + '"><i class="bi bi-trash"></i></button>' +
                '<div class="small text-truncate px-1" title="' + item.filename + '">' + item.filename + '</div>' +
              '</div>';
            col.querySelector('.media-picker-item').addEventListener('click', function (e) {
              if (e.target.closest('.media-picker-delete')) return;
              selectMedia(item);
            });
            col.querySelector('.media-picker-delete').addEventListener('click', function (e) {
              e.stopPropagation();
              deleteMedia(item);
            });
            grid.appendChild(col);
          });
        }

        function selectMedia(item) {
          if (!targetInput) { modal.hide(); return; }
          targetInput.value = item.url;
          // Same event the field would fire if typed by hand — the existing
          // per-block preview/dirty-tracking/history listeners already
          // react to 'input' on every text field, nothing new to wire up.
          targetInput.dispatchEvent(new Event('input', { bubbles: true }));
          modal.hide();
        }

        function deleteMedia(item) {
          if (!confirm(@json(__('Delete this file? Any block still referencing its URL will show a broken link.')))) return;
          fetch(@json(url('/admin/media')) + '/' + item.id, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
          }).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            items = null;
            loadMediaLibrary();
          }).catch(function () { alert(@json(__('Delete failed.'))); });
        }

        uploadInput.addEventListener('change', function () {
          var file = uploadInput.files[0];
          if (!file) return;
          var fd = new FormData();
          fd.append('file', file);
          status.textContent = @json(__('Uploading…'));
          fetch(@json(route('admin.media.store')), {
            method: 'POST',
            body: fd,
            headers: { 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
          }).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.json();
          }).then(function (created) {
            items = items ? [created].concat(items) : [created];
            renderGrid();
            uploadInput.value = '';
          }).catch(function () {
            status.textContent = @json(__('Upload failed.'));
            uploadInput.value = '';
          });
        });
      })();
    </script>
  @endpush
@endsection
