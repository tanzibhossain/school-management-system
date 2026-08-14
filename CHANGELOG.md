# Changelog

All notable changes to this project are documented here. Format loosely
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versioning
follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Fixed
- Documented that a plain `docker compose down` does not stop the profile-gated `ai-detector` service (it keeps running, and restarts on reboot) — use `docker compose stop ai-detector` or `docker compose --profile ai-detector down` instead. No code change; this is a Docker Compose profile-resolution behavior, not a bug in the service itself.
- `docker compose build`/`up --build` for `app`, `horizon`, and `scheduler` failed outright (`docker-php-ext-configure gd`, exit code 2) after an automatic dependency update bumped the Docker base image from PHP 8.3 to 8.5 — PHP 8.4 changed how the `gd` extension detects `libjpeg`/`libfreetype`, and the Dockerfile was missing the now-required `pkg-config` package.
- After the `pkg-config` fix above, the same build still failed one step later (`cp: cannot stat 'modules/*'` installing the `gd` shared extension) — `gd` now builds in its own isolated `docker-php-ext-install` call instead of being bundled with several other extensions in one invocation.

## [1.4.2] — 2026-08-06

### Added
- Notices block: Style tab now has targeted Heading, Card Background, Card Date, Card Title, Card Text, and Card Icon color fields.
- Staff block: Style tab now has targeted Heading, Avatar Ring, Avatar Text, Name, and Designation color fields.
- Hero banner block: Style tab now has Title, Subtitle, Button Text/Background, and Button Hover Text/Background color fields, plus an explicit Background Image / Solid Color toggle — only one is ever applied, instead of an image silently overriding an unused color field.
- Announcement bar block: Style tab now has targeted Message Text and Link Text color fields (background color already worked via the existing Advanced tab field).
- Every block's Advanced tab Background section now offers a third option, Gradient, alongside Image and Solid Color — pick a start color, end color, and direction.
- Margin, Padding, Border Width, and Border Radius controls: the four side inputs now sit flush against each other with only the outer corners rounded (no more separate T/B/L/R labels breaking them up), plus a new link-values button that, when toggled on, copies whatever you type into one box into the other three.
- Every block's Advanced tab now has an "ID & Class" section — set a custom HTML id and/or one or more CSS class names on a block for your own custom CSS or JavaScript to hook into.

### Changed
- Background Image / Solid Color / Gradient is now one field per block, always on the Advanced tab, for every block type including Hero — previously Hero kept its own copy of this control on the Style tab (added when the toggle was first introduced), which duplicated the Advanced tab's field under the hood and could silently overwrite it depending on form submission order.

### Fixed
- Statistics block: Style tab colors and the entrance animation now actually apply. A single wrapper-level color/animation could never reach the heading or tile text (they each carry their own explicit CSS), so those settings visibly did nothing. Replaced with four targeted fields (Heading, Tile Background, Tile Number, Tile Subtext color) for this block only, and the entrance animation now plays on the heading and each tile individually instead of the whole section at once.
- Page builder: editing a block's Style tab could make its live preview go blank (content still in the DOM, just invisible) whenever that block had an entrance animation set — the preview's fast per-block update path never told the page's scroll-reveal animation about newly-inserted elements, so they stayed permanently hidden instead of fading in.
- Hero banner block: Background Color now applies to the block's own section (consistent with every other block) instead of the inner header element, which used to paint over it and hide it completely.
- Notices block: added a Card Icon color field for the notice icon badge.
- Staff block: added an Avatar Text color field for the initial-letter avatar shown when a member has no photo.
- Hero banner block: fixed the Style tab's Background Color field silently doing nothing — it shared its underlying field name with the unrelated, always-present generic Background color field on the Advanced tab, so submitting the form could overwrite whichever one the admin had actually set.
- Hero banner block: a Background Color set on the block would previously be applied to the (invisible) wrapper element and never actually show, because the hero's own gradient/image sits on top of it. Now applies directly to the visible hero element, gated behind the new Image/Solid Color toggle.
- Public page rendering cache: strengthened the cache key for a published page so it can never serve a stale render for the wrong page — only ever observed under the automated test suite's in-memory database, not in normal operation.

## [1.4.1] — 2026-08-01

### Added
- Free, self-hosted alternative to the paid Anthropic AI checker in LMS — switch with one setting.

### Fixed
- AI checker score is now always kept within the expected range, and no longer builds/starts by default.
- Test suite no longer makes real network calls when a developer has the self-hosted checker enabled locally.
- Updated README, CLAUDE.md, and AGENTS.md to match the current codebase (both were out of date).
- Fixed the self-hosted AI checker container failing to build with a mismatched, non-CPU-only PyTorch install.
- Fixed the self-hosted AI checker crashing on startup after an automatic dependency update broke it, then properly migrated to and verified the newer version instead of just reverting.
- Documented that the self-hosted checker is less reliable on very short or casual submissions.
- Fixed the self-hosted AI checker printing an "unauthenticated requests" warning and unnecessarily phoning home to Hugging Face on every startup, despite already having everything it needs baked into the image.
- Applied a further batch of routine dependency updates to the self-hosted AI checker (transformers, fastapi, uvicorn, pydantic, safetensors), each verified with a real build and smoke test before merging.

## [1.4.0] — 2026-07-31

### Added
- Multi-language support for pages, menus, school info, staff, departments, and announcements.
- AI-assisted translation button, for both website content and admin UI text.
- Automatic Bengali date and digit formatting across the public site.
- Bilingual (English + Bengali) sample data for the whole app, so it's ready to demo out of the box.
- Translation status indicators on the Staff, Department, Page, and Announcement lists.
- Sample data for features that had none before: certificates, ID cards, SMS, payroll, loans, refunds, holidays, and contact messages.
- Refreshed and cleaned up Bengali translations using real usage data.

### Fixed
- Your own admin language setting no longer changes what visitors see on the public site.
- Fixed a crash when browsing the admin panel in Bengali.
- Fixed a bug where AI-translating a menu could scramble its order.
- Several public-site details (dates, phone numbers, addresses, the admission form) were ignoring the language switcher — now translated properly.
- Staff and announcement translations weren't showing up on the public site.
- The AI-translate button no longer closes your edit form unexpectedly.
- General code-quality cleanup: fixed test failures and static-analysis warnings.
- Cleaned up roughly 30 incorrect or awkward Bengali translations.
- Some admission form labels stayed in English under Bengali — now translate correctly.
- Cleaned up duplicate entries in the translation system.

### Changed
- Removed a redundant "copy" button now that AI-translate does the same job.

## [1.3.4] — 2026-07-31

### Added
- Wired up ~19 dormant site-theming settings (fonts, colors, buttons, background) via a new "Advanced Theme" section — fully backward compatible.
- Font choices are now validated against an allow-list to prevent CSS/HTML injection.
- Redesigned the public header into a single sticky bar that shrinks on scroll, plus a new "Apply Now" button and a fluid heading type scale.
- Two new page-builder blocks: a dismissible Announcement Bar and an FAQ accordion.

### Changed
- Demo school now showcases the new theming, announcement bar, and FAQ blocks.

### Fixed
- Fixed the nav underline and Bootstrap's dropdown arrow visually colliding on submenu items.

## [1.3.3] — 2026-07-28

### Added
- Shared cPanel hosting support: local-disk fallback when MinIO isn't configured, plus a full deployment guide.
- Version integrity checking — validates the app's VERSION file and can verify it against git tags.
- Added a safe deployment script and VPS/cPanel update documentation.

### Changed
- Routine dependency bumps (Laravel framework/Pint, concurrently).

### Fixed
- Removed leftover debug console logging from the page-builder's Admission Form editor.
- Fixed `scripts/deploy.sh` not being executable after cloning.
- Fixed stale caching examples in the project docs that could reintroduce a cPanel-breaking bug.
- Fixed one remaining caching call that would break page rendering on shared hosting.
- Fixed the health-check endpoint hardcoding a Redis connection, breaking it on non-Redis deployments.
- Moved the app version to a git-tracked file so it actually updates on every deploy.

### Security
- Patched a denial-of-service vulnerability in a dev-only dependency (via `concurrently`).

## [1.3.2] — 2026-07-27

### Added
- Gallery Photo/Video blocks now open a lightbox with prev/next navigation instead of leaving the page.
- Subtle hover/motion polish across the public site, respecting reduced-motion preferences.

### Changed
- Refreshed the public site's design tokens for a more minimal, consistent look.
- The school's accent color is now actually used (nav underline).
- Restyled the homepage and every page-builder block — visual polish only, no functional changes.

### Fixed
- Fixed the Admission Form block rendering full-width instead of the normal content column.
- Fixed a 500 error on any page using the Admission Form block.
- Fixed muted text and several hardcoded English strings not using the translation system.

## [1.3.1] — 2026-07-25

### Added
- Submit buttons now show a spinner and disable themselves while a form is submitting.

### Changed
- Unified the admin panel's two conflicting color systems into one consistent brand palette.
- Page headers can now show multiple action buttons.
- All admin views use a shared badge component instead of raw markup.

### Removed
- Deleted a dead, unrouted student-detail page and an unused dark-mode CSS block.

### Fixed
- Fixed ~250 lines of corrupted CSS from a bad copy-paste.
- Removed a dark-mode CSS block that had no way to actually be enabled.

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
- Per-page SEO fields (meta title, description, Open Graph image) with matching Twitter Card tags.
- Duplicate Page and Save as Template actions, plus a screen for managing saved templates.
- Media library with drag-and-drop upload, image picker, and alt-text editing.
- Autosave with local crash recovery and a conflict warning if the page changed elsewhere.
- Public page rendering is now cached and invalidated automatically on publish.
- Reorganized the block editor's Layout tab into Layout/Border/Background/Responsive sections.
- Accessibility improvements: action announcements, restored focus, and aria-labels across the canvas.

### Changed
- Padding/margin controls moved to the Advanced tab as four-box controls.
- Block reordering is drag-only now (removed the Move Up/Down buttons).
- Media fields show a live thumbnail preview instead of a broken-image icon.
- Page editor sidebar now has a fixed minimum width.

### Fixed
- Registered a missing filesystem disk so the media library can actually store uploads.
- Fixed inconsistent page-layout ordering when timestamps tie.
- Resolved 14 static-analysis errors.
- Fixed clipped status badges and a hidden Media Library modal in fullscreen mode.
- Fixed double-HTML-escaped page titles and meta tags.
- Fixed a silently-skipped test file and two incorrect test assertions.

## [1.2.0] — 2026-07-24

### Added
- Elementor-style live page builder: fullscreen canvas, resizable sidebar, live preview, and a responsive viewport toolbar.
- Click-to-select, drag-to-reorder, and copy/paste style on the live canvas.
- Drag-and-drop block placement, including into nested containers.
- 8 new block types, including two layout blocks that hold nested children up to 6 levels deep.
- Per-block Style and Layout tabs, applied consistently across every block type.
- Session undo/redo, page revision history, and copy/paste block style.
- Scheduled dependency updates via Dependabot.

### Changed
- Page editor sidebar is now resizable and remembers its width.
- Block tabs are smaller and more clearly show the active tab.

### Fixed
- Fixed a crash when rendering a saved page with a populated nested block.
- Fixed undo/redo dropping a nested block's children.
- Fixed the responsive viewport toolbar not actually resizing the live preview.
- Fixed an unclickable sidebar-resize divider.
- Removed dead legacy rich-text-editor code.
- Fixed a console error from the live preview.

### Security
- Rate-limited login and two-factor verification (5 attempts/minute).
- Changing your password or disabling two-factor auth now signs out every other session.
- Requesting an email change now notifies the current address, with a way to cancel it.

## [1.0.1] — 2026-07-23

### Added
- Self-service Account & Security page: change name/password/email, enable two-factor auth, manage sessions.
- Placeholder favicon across every layout.
- Release version shown in the admin panel footer.

### Fixed
- Selected language no longer reverts to English after a page refresh.
- Completed Bangla translation coverage across the admin panel.
- Fixed a translation bug where one string could corrupt an unrelated one sharing its prefix.
- Fixed the session/device list always showing no other active sessions.

## [1.0.0] — 2026-07-22

First tagged release.

### Added
- 26 modules: School, Academic, User/Auth, Student, Staff, Announcement, FeeItem, Payment (bKash, SSLCommerz, Stripe, PayPal), Examination, Attendance, Mark, Leave, Loan, Certificate, IdCard, Report, Sms, DataImport, OnlineAdmission, Website, Payroll, LMS, Library, Transport, Messaging, and Language.
- Server-rendered Laravel Blade + Bootstrap 5 admin panel with session auth.
- 578 automated tests; CI runs the suite, Pint, and static analysis on every push and pull request.
- AGPL-3.0 license.

[1.2.0]: https://github.com/tanzibhossain/school-management-system/compare/v1.0.1...v1.2.0
[1.0.1]: https://github.com/tanzibhossain/school-management-system/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/tanzibhossain/school-management-system/releases/tag/v1.0.0
