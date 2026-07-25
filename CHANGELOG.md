# Changelog

All notable changes to this project are documented here. Format loosely
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versioning
follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Dependencies
- `laravel/framework` 13.18.0 → 13.21.1, `laravel/horizon` 5.47.2 → 5.48.1, `laravel/sanctum` 4.3.2 → 4.3.3
- `spatie/laravel-permission` 8.1.0 → 8.3.0
- `league/flysystem-aws-s3-v3` 3.35.1 → 3.35.2
- `phpoffice/phpspreadsheet` 1.30.5 → 1.30.6
- `nunomaduro/collision` 8.9.4 → 8.9.5
- `concurrently` (npm, dev) ^9.0.1 → ^10.0.3
- `actions/checkout` v6 → v7 in all CI workflows
- Docker base image `php:8.3-fpm` → `php:8.5-fpm`

## [1.3.0] — 2026-07-24

### Added
- Per-page SEO fields (meta title, meta description, Open Graph image), with matching Twitter Card tags on
  the public site. A page's own value overrides the site-wide default.
- Duplicate Page and Save as Template actions in the page editor, plus a screen for renaming/deleting saved
  templates.
- Media library: upload and picker wired into image/poster block fields, drag-and-drop upload, and alt-text
  editing.
- Autosave: local crash-recovery snapshots while editing, and a warning if the page was published elsewhere
  since you started.
- Public page rendering is cached and invalidated automatically on publish.
- The block editor's Layout tab is now "Advanced," split into four collapsible sections: Layout (grid
  columns, margin/padding, width mode — Default/Full Width/Inline/Custom), Border (type, width, color,
  radius, shadow), Background (color, image, overlay), and Responsive (per-breakpoint visibility). The Style
  tab is now just text color and entrance animation.
- Accessibility: announcements for block add/remove/reorder/move actions, focus restored when the
  right-click context menu closes, and aria-labels across the canvas.
- Test coverage for the Advanced tab and Container/Grid nesting.

### Changed
- Padding and margin moved from the Style tab to the Advanced tab as four-box (top/bottom/left/right)
  controls.
- Removed the Move Up/Down buttons from block rows — reordering is drag-only now.
- Media fields show a live thumbnail preview; empty image/video blocks show a placeholder instead of a
  broken-image icon.
- Page editor sidebar has a fixed 250px minimum width (was a viewport-relative percentage that could shrink
  below 200px on laptop screens).

### Fixed
- Registered the missing `minio` filesystem disk, so the media library can actually store uploads.
- `Page::layouts()` now tie-breaks on `id` as well as `created_at`.
- Resolved 14 PHPStan (level 5) errors and baselined one false positive.
- Status badges no longer clip tall-script glyphs.
- Media Library modal is now visible when opened from inside the fullscreen page editor.
- Public page title, description, and Open Graph/Twitter meta tags were double-HTML-escaped (`&amp;amp;`
  instead of `&amp;`) — fixed.
- Fixed a parse error in `PageSeoMetaTagsTest.php` that had been silently skipping that entire test file.
- Fixed two incorrect assertions in the width tests.

## [1.2.0] — 2026-07-24

### Added
- Elementor-style live page builder for the Website module: a fullscreen canvas with a resizable
  block-layers sidebar, a live preview that renders through the same Blade views as the public site, and a
  responsive desktop/laptop/tablet/mobile viewport toolbar.
- Click-to-select, drag-to-reorder, and right-click Copy Style / Paste Style / Remove on the live canvas.
- Drag a block type from the Add Block panel straight onto the canvas, including into a Container/Grid.
- 8 new block types: Video (YouTube/Vimeo/Dailymotion/VideoPress/self-hosted), Button, Divider, Spacer,
  Icon, Google Maps, and two layout blocks — Container and Grid — that hold nested children.
- Nested blocks go arbitrarily deep (up to 6 levels) and are fully canvas-interactive at every depth.
- Per-block Style (padding/margin/background/color/radius/shadow/animation) and Layout (columns/visibility)
  tabs, applied consistently across every block type.
- Session undo/redo, page revision history with one-click restore, and copy/paste block style.
- The editor's Update/Publish button stays disabled until something actually changes.
- `.github/dependabot.yml` for scheduled dependency updates.

### Changed
- Page editor sidebar is resizable (12.5%–25% of viewport width) and remembers its width.
- Block tabs are smaller and fully bordered, with the active tab filled in the site's brand color.

### Fixed
- A saved page with a populated Container/Grid block could throw `Undefined array key "d"` on the public
  site and in the live preview.
- Undo/redo could drop a Container/Grid block's children when restoring a history snapshot.
- The responsive viewport toolbar wasn't actually resizing the live preview.
- The sidebar-resize divider was effectively unclickable — a CSS `overflow` rule was clipping its hit area.
- Removed dead TinyMCE code; rich text editing has always run on Quill.
- Fixed a `postMessage` console error from the live preview iframe.

### Security
- Rate-limited login and the two-factor challenge (5 attempts/minute) — neither had any throttling before.
- Changing your password or disabling two-factor authentication now signs out every other active session.
- Requesting an email change now also notifies the current address, with a link to cancel the change.

## [1.0.1] — 2026-07-23

### Added
- Self-service Account & Security page for every user (admin, staff, family): change name and password,
  change email (held pending until confirmed), enable two-factor authentication (TOTP with recovery codes),
  and manage active sessions.
- Placeholder favicon across every layout.
- Release version shown in the admin panel footer, read from `APP_VERSION`.

### Fixed
- Selected language no longer reverts to English after a page refresh.
- Completed Bangla translation coverage across the admin panel.
- Fixed a translation-engine bug where a source string containing a literal period could corrupt a shorter,
  unrelated key sharing its prefix.
- Fixed the session/device list always reporting "No other active sessions" — the session ID was never being
  persisted.

## [1.0.0] — 2026-07-22

First tagged release.

### Added
- 26 modules: School, Academic, User/Auth, Student, Staff, Announcement, FeeItem, Payment (bKash,
  SSLCommerz, Stripe, PayPal), Examination, Attendance, Mark, Leave, Loan, Certificate, IdCard, Report, Sms,
  DataImport, OnlineAdmission, Website, Payroll, LMS, Library, Transport, Messaging, and Language.
- Server-rendered Laravel Blade + Bootstrap 5 admin panel with session auth, reusing module Services
  directly.
- 578 automated tests; CI runs the suite, Pint, and Larastan/PHPStan on every push and pull request.
- AGPL-3.0 license.

[1.2.0]: https://github.com/tanzibhossain/school-management-system/compare/v1.0.1...v1.2.0
[1.0.1]: https://github.com/tanzibhossain/school-management-system/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/tanzibhossain/school-management-system/releases/tag/v1.0.0
