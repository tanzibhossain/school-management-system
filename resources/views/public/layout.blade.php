<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ ($appIsRtl ?? false) ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $s = $settings ?? null;
        $primary = $s->primary_color ?? '#1d4ed8';
        $accent = $s->accent_color ?? '#f59e0b';
        $heading = $s->heading_color ?? '#0f172a';
        $siteName = $s->site_name ?? ($school->name ?? 'Our School');
        $metaDesc = $s->meta_description ?? null;
        $faviconUrl = \App\Support\Media::url($s->favicon ?? null);
        $ogUrl = \App\Support\Media::url($s->og_image ?? null);
      @endphp
    <title>@yield('title', ($s->meta_title ?? null) ?: $siteName)</title>
    @if ($metaDesc)
    <meta name="description" content="{{ $metaDesc }}">@endif
    {{-- Falls back to the generic placeholder favicon until a school uploads its own. --}}
    <link rel="icon" href="{{ $faviconUrl ?: asset('favicon.ico') }}">
    <meta property="og:title" content="@yield('title', ($s->meta_title ?? null) ?: $siteName)">
    @if ($metaDesc)
    <meta property="og:description" content="{{ $metaDesc }}">@endif
    @if ($ogUrl)
    <meta property="og:image" content="{{ $ogUrl }}">@endif
    <meta property="og:type" content="website">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --brand:
                {{ $primary }}
            ;
            --brand-accent:
                {{ $accent }}
            ;
            --brand-heading:
                {{ $heading }}
            ;
        }

        body {
            color: #1f2937;
        }

        a {
            color: var(--brand);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--brand) !important;
        }

        .btn-brand {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }

        .btn-brand:hover {
            filter: brightness(.92);
            color: #fff;
        }

        .text-brand {
            color: var(--brand);
        }

        .hero {
            background: linear-gradient(135deg, var(--brand), color-mix(in srgb, var(--brand) 65%, #000));
            color: #fff;
        }

        .hero h1 {
            color: #fff;
            font-weight: 700;
        }

        .section-title {
            color: var(--brand-heading);
            font-weight: 700;
        }

        .stat-num {
            color: var(--brand);
            font-weight: 700;
            font-size: 2.25rem;
            line-height: 1;
        }

        .card {
            border: 0;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .06), 0 1px 3px rgba(16, 24, 40, .05);
        }

        footer {
            background: #0f172a;
            color: #cbd5e1;
        }

        footer a {
            color: #e2e8f0;
            text-decoration: none;
        }

        .pub-ticker {
            overflow: hidden;
            white-space: nowrap;
        }

        .pub-ticker-track {
            display: inline-block;
            padding-left: 100%;
            animation: pub-ticker 28s linear infinite;
        }

        .pub-ticker:hover .pub-ticker-track {
            animation-play-state: paused;
        }

        @keyframes pub-ticker {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        /* Block "entrance animation" presets (Style tab) — deliberately minimal:
           a short opacity/translate fade, once, the first time a block scrolls
           into view. Respects prefers-reduced-motion for accessibility. */
        .reveal {
            opacity: 0;
            transition: opacity .5s ease, transform .5s ease;
        }

        .reveal-up {
            transform: translateY(20px);
        }

        .reveal.is-visible {
            opacity: 1;
            transform: none;
        }

        @media (prefers-reduced-motion: reduce) {
            .reveal {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }

        @if ($s?->custom_css ?? false)
            {!! $s->custom_css !!}
        @endif

        /* Admin live-preview click-to-select — only active when this page is
           rendered inside the editor's iframe (see the gated script below and
           docs/modules/28-elementor-block-editor-plan.md Milestone 4). Inert
           otherwise: no visual effect on the real public site. */
        body.is-editor-preview [data-block-index] { cursor: pointer; }
        body.is-editor-preview .is-block-hover { outline: 2px dashed #6c8fff; outline-offset: -2px; }
        body.is-editor-preview .is-block-selected { outline: 2px solid var(--brand); outline-offset: -2px; }
        /* In-canvas drag-and-drop reordering + right-click context menu — see
           the gated script below. */
        body.is-editor-preview [data-block-index] { cursor: grab; }
        body.is-editor-preview [data-block-index].is-dragging { opacity: .35; cursor: grabbing; }
        body.is-editor-preview [data-block-index].drop-before { box-shadow: inset 0 3px 0 0 var(--brand); }
        body.is-editor-preview [data-block-index].drop-after { box-shadow: inset 0 -3px 0 0 var(--brand); }
        #editor-context-menu button:hover { background: #f1f3f5; }
        /* Dragging a new block in from the editor's Add Block panel (see the
           gated script below) — a subtle tint over the whole canvas so it's
           clear this is a valid drop target even before hovering a block. */
        body.is-editor-preview.is-external-drag-over { background: color-mix(in srgb, var(--brand) 5%, #fff); }
    </style>
</head>

<body>
    @include('public.partials.header')

    @yield('content')

    <footer class="py-5 mt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-5">
                    <h5 class="text-white mb-2">{{ $siteName }}</h5>
                    @if ($school?->address ?? false)
                    <p class="mb-1 small"><i class="bi bi-geo-alt"></i> {{ $school->address }}</p>@endif
                    @if ($school?->email ?? false)
                    <p class="mb-0 small"><i class="bi bi-envelope"></i> {{ $school->email }}</p>@endif
                </div>
                <div class="col-md-4">
                    <h6 class="text-white-50 text-uppercase small mb-2">{{ __('Quick Links') }}</h6>
                    <div class="d-flex flex-column gap-1 small">
                        <a href="{{ route('home') }}#notices">{{ __('Notices') }}</a>
                        <a href="{{ route('home') }}#results">{{ __('Check Results') }}</a>
                        <a href="{{ route('login') }}">{{ __('Portal Login') }}</a>
                    </div>
                </div>
                <div class="col-md-3">
                    <h6 class="text-white-50 text-uppercase small mb-2">{{ __('Portal') }}</h6>
                    <a href="{{ route('login') }}" class="btn btn-outline-light btn-sm">{{ __('Sign In') }}</a>
                </div>
            </div>
            <hr class="border-secondary my-4">
            <p class="small mb-0 text-center text-white-50">© {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Reveal blocks with a Style-tab "entrance animation" once, the first
        // time they scroll into view. No-op (blocks just render fully visible)
        // if IntersectionObserver isn't available or the user prefers reduced motion.
        (function () {
            var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var els = document.querySelectorAll('.reveal');
            if (reduced || !('IntersectionObserver' in window)) {
                els.forEach(function (el) { el.classList.add('is-visible'); });
                return;
            }
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: .15, rootMargin: '0px 0px -10% 0px' });
            els.forEach(function (el) { io.observe(el); });
        })();

        // Editor click-to-select bridge — no-op unless this document is
        // actually sitting inside the admin page-builder's preview iframe.
        // See resources/views/admin/website/pages/edit.blade.php for the
        // parent-side listener.
        (function () {
            if (window.self === window.top) return;
            document.body.classList.add('is-editor-preview');

            var selected = null;
            document.addEventListener('mouseover', function (e) {
                var el = e.target.closest('[data-block-index]');
                document.querySelectorAll('.is-block-hover').forEach(function (n) {
                    if (n !== el) n.classList.remove('is-block-hover');
                });
                if (el) el.classList.add('is-block-hover');
            });
            document.addEventListener('mouseout', function (e) {
                var el = e.target.closest('[data-block-index]');
                if (el) el.classList.remove('is-block-hover');
            });
            // Capture phase: intercept before a link/form inside the block
            // gets to act — this is a preview, clicks should select, not navigate.
            document.addEventListener('click', function (e) {
                var el = e.target.closest('[data-block-index]');
                if (!el) {
                    // Clicked the canvas background, not a block — tell the
                    // parent so it can collapse the sidebar back to its
                    // default panel (see edit.blade.php's click-outside handling).
                    if (selected) { selected.classList.remove('is-block-selected'); selected = null; }
                    window.parent.postMessage({ source: 'page-preview', type: 'deselect' }, '*');
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                if (selected) selected.classList.remove('is-block-selected');
                selected = el;
                el.classList.add('is-block-selected');
                // Posted with '*': this document is loaded via iframe.srcdoc, so
                // window.location.origin here serializes as the literal string
                // "null" (a known browser quirk for srcdoc documents) — passing
                // that as a targetOrigin throws "Invalid target origin 'null'".
                // The parent verifies the sender by e.source (the iframe's
                // contentWindow), not by origin, so this stays safe without it.
                window.parent.postMessage({
                    source: 'page-preview',
                    type: 'select-block',
                    group: el.dataset.blockGroup,
                    index: el.dataset.blockIndex,
                }, '*');
            }, true);

            // ── In-canvas drag-and-drop reordering ───────────────────────────
            // Native HTML5 DnD (draggable="true" is set server-side in this
            // editor-preview context only — see public/blocks/render.blade.php
            // and public/sidebar/render.blade.php). Dragging is confined to a
            // single group (main blocks vs sidebar blocks are separate arrays)
            // — the drop computes a full new index order and hands it to the
            // parent, which reorders the actual rail list (the source of
            // truth) and re-renders; this iframe doesn't move any DOM itself.
            var dragSrc = null;
            function clearDropMarkers() {
                document.querySelectorAll('.drop-before, .drop-after').forEach(function (n) {
                    n.classList.remove('drop-before', 'drop-after');
                });
            }
            document.addEventListener('dragstart', function (e) {
                var el = e.target.closest('[data-block-index]');
                if (!el) return;
                dragSrc = el;
                el.classList.add('is-dragging');
                if (e.dataTransfer) {
                    e.dataTransfer.effectAllowed = 'move';
                    try { e.dataTransfer.setData('text/plain', el.dataset.blockIndex); } catch (err) {}
                }
            });
            document.addEventListener('dragend', function () {
                if (dragSrc) dragSrc.classList.remove('is-dragging');
                clearDropMarkers();
                dragSrc = null;
            });
            // ── Drag a NEW block in from the editor's Add Block panel ────────
            // The drag source lives in the PARENT document's sidebar (see
            // edit.blade.php), not in here — HTML5 dragstart/dragover/drop
            // fire across the iframe boundary natively (it's a browser-level
            // gesture, not restricted by same-origin/sandbox the way script
            // access is), but dataTransfer.getData() can only be READ on
            // drop, never during dragover (a spec-level security
            // restriction) — so during dragover we only know an external
            // add-block drag is in progress (via .types, which IS readable
            // early) and show a generic insertion indicator; the actual
            // group/type payload is read once, on drop.
            var externalTarget = null;
            function isExternalBlockDrag(e) {
                return !dragSrc && !!e.dataTransfer
                    && Array.prototype.indexOf.call(e.dataTransfer.types, 'application/x-block-type') !== -1;
            }
            document.addEventListener('dragover', function (e) {
                if (dragSrc) {
                    var el = e.target.closest('[data-block-index]');
                    if (!el || el === dragSrc || el.dataset.blockGroup !== dragSrc.dataset.blockGroup) return;
                    e.preventDefault();
                    clearDropMarkers();
                    var rect = el.getBoundingClientRect();
                    var before = (e.clientY - rect.top) < rect.height / 2;
                    el.classList.add(before ? 'drop-before' : 'drop-after');
                    return;
                }
                if (!isExternalBlockDrag(e)) return;
                e.preventDefault();
                document.body.classList.add('is-external-drag-over');
                clearDropMarkers();
                var el = e.target.closest('[data-block-index]');
                if (el) {
                    var rect = el.getBoundingClientRect();
                    var before = (e.clientY - rect.top) < rect.height / 2;
                    el.classList.add(before ? 'drop-before' : 'drop-after');
                    externalTarget = { index: parseInt(el.dataset.blockIndex, 10), group: el.dataset.blockGroup, before: before };
                } else {
                    externalTarget = null; // over empty canvas — appends at the end on drop
                }
            });
            document.addEventListener('dragleave', function (e) {
                // relatedTarget is null when the pointer leaves the document
                // entirely (vs. just moving between two child elements).
                if (e.relatedTarget === null) {
                    clearDropMarkers();
                    document.body.classList.remove('is-external-drag-over');
                }
            });
            document.addEventListener('drop', function (e) {
                document.body.classList.remove('is-external-drag-over');
                if (dragSrc) {
                    var el = e.target.closest('[data-block-index]');
                    if (!el || el === dragSrc || el.dataset.blockGroup !== dragSrc.dataset.blockGroup) return;
                    e.preventDefault();
                    var group = dragSrc.dataset.blockGroup;
                    var nodes = Array.prototype.slice.call(
                        document.querySelectorAll('[data-block-index][data-block-group="' + group + '"]')
                    );
                    var order = nodes.map(function (n) { return parseInt(n.dataset.blockIndex, 10); });
                    var fromVal = parseInt(dragSrc.dataset.blockIndex, 10);
                    var toVal = parseInt(el.dataset.blockIndex, 10);
                    var rect = el.getBoundingClientRect();
                    var before = (e.clientY - rect.top) < rect.height / 2;
                    order.splice(order.indexOf(fromVal), 1);
                    var insertAt = order.indexOf(toVal) + (before ? 0 : 1);
                    order.splice(insertAt, 0, fromVal);
                    clearDropMarkers();
                    window.parent.postMessage({ source: 'page-preview', type: 'reorder-blocks', group: group, order: order }, '*');
                    return;
                }
                if (!e.dataTransfer) return;
                var raw = e.dataTransfer.getData('application/x-block-type') || e.dataTransfer.getData('text/plain');
                if (!raw) return;
                var payload;
                try { payload = JSON.parse(raw); } catch (err) { return; }
                if (!payload || !payload.type || !payload.group) return;
                e.preventDefault();
                clearDropMarkers();
                // Only honor the hovered insertion point if it's the SAME
                // group as what's being dropped (you can't insert a
                // Sidebar-only block type among main content blocks, or vice
                // versa) — otherwise fall back to appending at the end of
                // the correct group, same as clicking the picker item would.
                var useTarget = externalTarget && externalTarget.group === payload.group;
                window.parent.postMessage({
                    source: 'page-preview',
                    type: 'add-block-at',
                    group: payload.group,
                    blockType: payload.type,
                    index: useTarget ? externalTarget.index : null,
                    before: useTarget ? externalTarget.before : null,
                }, '*');
                externalTarget = null;
            });

            // ── Right-click context menu: Copy Style / Paste Style / Delete ──
            // A small hand-rolled menu (Bootstrap is loaded in this document,
            // but its dropdown JS is built around a trigger element, not an
            // arbitrary cursor position, so a plain absolutely-positioned menu
            // is simpler here). Actions are dispatched to the parent, which
            // already owns the copy/paste-style clipboard and block removal.
            function closeContextMenu() {
                var m = document.getElementById('editor-context-menu');
                if (m) m.remove();
            }
            document.addEventListener('contextmenu', function (e) {
                var el = e.target.closest('[data-block-index]');
                closeContextMenu();
                if (!el) return;
                e.preventDefault();
                var menu = document.createElement('div');
                menu.id = 'editor-context-menu';
                menu.className = 'shadow-sm';
                menu.style.cssText = 'position:fixed;z-index:99999;background:#fff;border:1px solid rgba(0,0,0,.15);'
                    + 'border-radius:.375rem;min-width:170px;padding:.25rem 0;font-family:system-ui,-apple-system,sans-serif;font-size:.875rem;';
                [
                    { action: 'copy', icon: 'bi-clipboard', label: @json(__('Copy Style')) },
                    { action: 'paste', icon: 'bi-clipboard-check', label: @json(__('Paste Style')) },
                    { action: 'delete', icon: 'bi-trash', label: @json(__('Remove')), danger: true },
                ].forEach(function (a) {
                    var item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'btn btn-sm w-100 text-start border-0 rounded-0 d-flex align-items-center gap-2 px-3 py-1' + (a.danger ? ' text-danger' : '');
                    item.innerHTML = '<i class="bi ' + a.icon + '"></i> ' + a.label;
                    item.addEventListener('click', function (ev) {
                        ev.stopPropagation();
                        window.parent.postMessage({
                            source: 'page-preview', type: 'context-action', action: a.action,
                            group: el.dataset.blockGroup, index: el.dataset.blockIndex,
                        }, '*');
                        closeContextMenu();
                    });
                    menu.appendChild(item);
                });
                document.body.appendChild(menu);
                var rect = menu.getBoundingClientRect();
                var left = e.clientX, top = e.clientY;
                if (left + rect.width > window.innerWidth) left = window.innerWidth - rect.width - 8;
                if (top + rect.height > window.innerHeight) top = window.innerHeight - rect.height - 8;
                menu.style.left = Math.max(4, left) + 'px';
                menu.style.top = Math.max(4, top) + 'px';
            }, true);
            document.addEventListener('click', closeContextMenu);
            document.addEventListener('scroll', closeContextMenu, true);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeContextMenu(); });
        })();
    </script>
</body>

</html>