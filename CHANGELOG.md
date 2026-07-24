# Changelog

All notable changes to this project are documented here. Format loosely
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versioning
follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.3.0] — 2026-07-24

### Added
- Per-page **SEO fields** in the page editor — meta title, meta description, and Open Graph image, wired
  through to the public site's `<title>`, `og:*`, and (newly) **Twitter Card** meta tags, with a page's own
  value winning over the site-wide Website > Settings default.
- **Duplicate Page** and **Save as Template** actions in the page editor, plus a management screen for
  renaming/deleting saved page templates.
- **Media library**: a real upload + picker wired into every image/poster block field (previously scaffolded
  but unreachable), drag-and-drop upload, and per-image alt-text editing.
- **Autosave** — the editor now saves a local crash-recovery snapshot as you work, and warns if it detects the
  page was published elsewhere since you started editing (concurrent-edit protection).
- Public page rendering is now **cached** (keyed by the published layout's id), invalidated automatically on
  publish.
- The block editor's **Advanced tab** (formerly "Layout") is restructured into four independently-collapsible
  sections matching Elementor's own panel: **Layout** (grid columns, margin/padding 4-box T/B/L/R controls, a
  Width mode dropdown — Default/Full Width/Inline (Auto)/Custom with a %/px/em/rem unit picker), **Border**
  (type, 4-box width, single color, 4-box radius, shadow), **Background** (color/image/overlay, moved from the
  Style tab), and **Responsive** (per-breakpoint hide switches). A block's own Style tab is now just text color
  and entrance animation.
- Accessibility: live-region announcements for every block add/remove/reorder/move action, focus restored to
  the triggering element when the canvas right-click context menu closes, and aria-labels on the drag handle,
  context menu, and every canvas-selectable block (including nested ones).
- Test coverage for the Style/Layout(Advanced) tabs and Container/Grid nesting (width modes, border
  safety rule, per-side-radius-over-legacy precedence, and the editor's own rendered markup).

### Changed
- Padding/margin moved off the Style tab onto the Advanced tab as a 4-box (Top/Bottom/Left/Right) control, in
  the same pass that split Advanced into its four collapsible sections above.
- Removed the Move Up/Down buttons from block rows — reordering is drag-only now (the drag handle was already
  the primary way most people reordered blocks; this is a real accessibility trade-off, noted below).
- Media fields show a live thumbnail preview as soon as a URL is set, both in the editor and inside the block
  itself; image/image-text/video blocks show a neutral icon-and-text placeholder instead of a broken-image icon
  or plain text when nothing is set yet.
- Page editor sidebar's minimum width is now a fixed 250px (was 12.5% of viewport width, which still shrank
  to ~171px on a real 1366px laptop screen — too narrow for its own form controls).

### Fixed
- A missing `'minio'` filesystem disk definition meant the Website media library couldn't actually store
  anything until the disk (and its bucket) existed — now registered and auto-created.
- `Page::layouts()` could tie-break incorrectly on `created_at` alone when two layout rows shared a timestamp;
  now tie-breaks on `id` as well.
- 14 real PHPStan (level 5) errors resolved, plus one intentionally baselined nullsafe false-positive.
- Status badges were clipping tall-script glyphs — added top/bottom padding.
- The Media Library modal was invisible when opened from inside the fullscreen page editor (a CSS rule written
  for native `<dialog>` semantics, not Bootstrap 5's `.show`-class toggle, was missing from the editor's own
  layout shell).
- **Public page `<title>`, `og:title`, `twitter:title`, meta description, and `og:image` were all
  double-HTML-escaped** — a title or description containing `&`, `"`, `<`, or `>` rendered with doubled
  entities (`&amp;amp;` instead of `&amp;`). Caused by Blade's inline `@section('name', $value)` form silently
  escaping `$value` itself, on top of the layout's own `{{ }}` escaping it again on output.
- A PHP parse error in `PageSeoMetaTagsTest.php`'s own docblock (a literal `*/` inside the comment text closed
  it early) had been silently preventing that entire test file from running at all — fixing it surfaced the
  double-escaping bug above, which a real `docker compose exec app php artisan test --parallel` run had never
  actually exercised until now.
- Two real assertion bugs in the new width tests, caught by that same full parallel run: a strict
  `assertSame(75.0, ...)` that didn't account for MySQL's JSON column normalizing a whole-number float back to
  an int on round-trip, and an `assertDontSee('width:')` that false-positive-failed against the editor-preview
  bridge script's own always-present `min-width:170px` context-menu style.

### Notes
- **Accessibility regression, noted plainly rather than glossed over**: removing the Move Up/Down buttons
  (see Changed, above) removes the only keyboard-operable way to reorder a block — dragging has no keyboard
  equivalent. Flagged as a known, deliberate trade-off at the user's explicit request, not an oversight.
- Most of this cycle's admin/editor-facing changes (Advanced tab redesign, media library wiring, autosave,
  SEO fields, a11y passes) were built and verified via brace/paren/bracket balance checks, `node --check` on
  extracted inline scripts, and `json.load` validation of `bn.json` — no PHP/browser was available in the
  environment they were built in. Two real `docker compose exec app php artisan test --parallel` runs (611
  tests) did happen this cycle, though, and caught three genuine bugs above (the double-escape, the parse
  error, and the two width-test assertion bugs) that no amount of static checking would have found — a good
  argument for running the full suite again before relying on anything in this release, especially the newer
  Advanced tab controls and the media library upload/drag-and-drop paths.

## [1.2.0] — 2026-07-24

### Added
- **Elementor-style live page builder** for the Website module's block editor — replaces the old form-only
  editor with a fullscreen, WYSIWYG canvas: a resizable block-layers sidebar (Add Block / block settings /
  page settings / revision history panels, remembers its width across pages), a live preview iframe that
  re-renders through the exact same Blade views as the public site (never a separate preview
  re-implementation that could drift), and a responsive desktop/laptop/tablet/mobile viewport toolbar.
- Click-to-select, drag-to-reorder, and right-click Copy Style / Paste Style / Remove directly on the live
  canvas, instead of hunting through a separate settings form for the right block.
- Drag a block type straight from the Add Block panel onto the canvas to insert it at an exact position —
  including directly into a Container/Grid.
- 8 new block types: **Video** (YouTube/Vimeo/Dailymotion/VideoPress/self-hosted, with the full Elementor-
  style option set — start/end time, autoplay/mute/loop/controls/download, poster, preload), **Button**,
  **Divider**, **Spacer**, **Icon**, **Google Maps**, and two layout blocks — **Container** (flex row/column)
  and **Grid** (responsive column count) — that hold their own nested child blocks.
- Nested blocks can now go arbitrarily deep (a Container can hold another Container, up to 6 levels) and are
  fully canvas-interactive at every depth: click-select, drag-reorder, drag directly into or out of a
  container or between containers, and right-click — the same as any top-level block.
- Per-block **Style** (padding/margin/background/text color/radius/shadow/entrance animation) and **Layout**
  (per-breakpoint column count and visibility) tabs, applied consistently across every block type on the
  public site.
- Session undo/redo, page revision history with one-click restore, and copy/paste block style between
  blocks.
- The page editor's Update/Publish button now stays disabled until something has actually changed since the
  page was opened — including re-disabling itself if you undo all the way back to that starting state.
- `.github/dependabot.yml` for scheduled composer/npm/docker/github-actions dependency updates.

### Changed
- Page editor sidebar is resizable (12.5%–25% of viewport width, drag the divider) and remembers its width
  the next time any page is opened for editing.
- A block's Content/Style/Layout tabs are smaller, fully bordered, and the active tab is now filled with the
  site's brand color instead of Bootstrap's default top-border-only styling.

### Fixed
- A saved page with a populated Container or Grid block could throw `Undefined array key "d"` on the public
  site (and in the editor's own live preview) — the block's resolved child data was being silently discarded
  by an array-merge ordering bug.
- Undo/redo could silently drop every child of a Container/Grid block when restoring a history snapshot; it
  now round-trips a container's full nested structure correctly regardless of how deep it goes.
- The responsive viewport toolbar (desktop/laptop/tablet/mobile) wasn't actually resizing the live preview.
- The page editor's sidebar-resize divider was effectively unclickable — a CSS `overflow` rule was clipping
  away most of its draggable hit area.
- Removed dead TinyMCE integration code that was never actually loaded in this app — rich text editing has
  always really been powered by Quill; a rich-text block added after the initial page load previously got an
  inert, non-functioning editor because of this leftover code path.
- Fixed a `postMessage` console error when interacting with the live preview iframe.

### Security
- Rate-limited login and the two-factor challenge (5 attempts/minute, keyed
  by email+IP and by the pending 2FA user+IP respectively) — neither had any
  throttling before, so a 6-digit TOTP code was brute-forceable.
- Changing your password or disabling two-factor authentication now signs
  out every other active session automatically, instead of leaving a
  possibly-compromised session logged in.
- Requesting an email change now also notifies the *current* address with a
  "wasn't you?" link that cancels the pending change without requiring
  login — previously only the new address heard about the change at all.

### Notes
- The block editor's recursive Container/Grid nesting, canvas drag-into-container, and structure-aware
  undo/redo are the largest single change to the Website module since 1.0.0 and have not been run through
  Pint, PHPStan/Larastan, PHPUnit, or a real browser in the environment this was built in — verify locally
  (especially deeply nested layouts and the drag-and-drop interactions) before relying on this in production.

## [1.0.1] — 2026-07-23

### Added
- Self-service **Account & Security** page for every user, available from all
  three portals (admin, staff, and family): change name and password, change
  email address (held pending until confirmed via a signed link sent to the
  new address), enable two-factor authentication via an authenticator app
  (TOTP, with QR setup and one-time recovery codes), and manage active
  sessions — see which devices are signed in and sign any of them out
  individually or all at once.
- Placeholder favicon, wired into every layout (public site, admin, staff,
  family portal, login, and two-factor challenge screens) so browser tabs no
  longer show a broken icon.
- Release version shown in the admin panel footer, read from a new
  `APP_VERSION` environment variable so it can be bumped per deploy without a
  code change.

### Fixed
- Selected language no longer reverts to English after a page refresh (a
  Redis cache config value was silently discarding cached translation
  objects).
- Completed Bangla translation coverage across the admin panel — the
  sidebar, page headers/breadcrumbs/action buttons, both DataTables
  initializers, the command palette, the login screen, and payment gateway
  settings labels previously stayed in English regardless of the selected
  language.
- Fixed a translation-engine bug where an English source string containing a
  literal period (e.g. "Search...", "Email address updated.") could corrupt
  the cached value of a shorter, unrelated key sharing its prefix —
  occasionally surfacing as a fatal error on pages using the corrupted key.
- Fixed the new session/device list always reporting "No other active
  sessions," even when signed in from multiple browsers at once, because the
  session ID was never actually being persisted.

### Notes
- The Account & Security feature ships without dedicated automated tests in
  this release — manually verify the 2FA and email-change flows on your own
  deployment before relying on them in production.

## [1.0.0] — 2026-07-22

First tagged release.

### Added
- 26 modules: School, Academic, User/Auth, Student, Staff, Announcement,
  FeeItem, Payment (bKash, SSLCommerz, Stripe, PayPal), Examination,
  Attendance, Mark, Leave, Loan, Certificate, IdCard, Report, Sms,
  DataImport, OnlineAdmission, Website (CMS + block-based homepage,
  drag-drop menus), Payroll, LMS, Library, Transport, Messaging, and
  Language (DB-backed translations, RTL support, scan + editor UI).
- Server-rendered Laravel Blade + Bootstrap 5 admin panel (session auth),
  reusing module Services directly — no separate frontend/API layer for
  the admin UI.
- 578 automated tests; CI runs the suite (in-memory SQLite), Laravel Pint
  (code style), and Larastan/PHPStan level 5 (static analysis) on every
  push and pull request.
- AGPL-3.0 license.

### Notes
- Single-school, self-hosted by design — no multi-tenant SaaS layer.
- Seeded demo credentials (admin/staff/student/guardian logins, MinIO,
  MySQL) are for local development only — see the README's Quick Start
  for the full list and the warning to change them before production use.

[1.2.0]: https://github.com/tanzibhossain/school-management-system/compare/v1.0.1...v1.2.0
[1.0.1]: https://github.com/tanzibhossain/school-management-system/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/tanzibhossain/school-management-system/releases/tag/v1.0.0
