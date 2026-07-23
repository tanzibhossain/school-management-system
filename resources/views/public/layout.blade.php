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
                if (!el) return;
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
        })();
    </script>
</body>

</html>