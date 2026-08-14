# Changelog

All notable changes to this project are documented here. Format loosely
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versioning
follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Fixed
- Documented that `docker compose down` doesn't stop the profile-gated `ai-detector` service — use `docker compose stop ai-detector` instead.
- Reverted the Docker base image from PHP 8.5 back to 8.3 after a chain of build failures (`gd`, bundled extensions, `opcache`) and a hard PHP-version cap in `phpoffice/phpspreadsheet`.

## [1.4.2] — 2026-08-06

### Added
- Notices block: targeted color fields (Heading, Card Background/Date/Title/Text/Icon).
- Staff block: targeted color fields (Heading, Avatar Ring/Text, Name, Designation).
- Hero banner block: Title/Subtitle/Button color fields, plus a Background Image / Solid Color toggle.
- Announcement bar block: Message Text and Link Text color fields.
- Gradient background option added alongside Image and Solid Color for every block.
- Margin, Padding, Border Width, and Border Radius controls redesigned as linked four-side inputs.
- Custom ID & Class section added to every block's Advanced tab.

### Changed
- Background Image/Color/Gradient is now one shared Advanced-tab field for every block, including Hero.

### Fixed
- Statistics block: Style tab colors and entrance animation now apply correctly.
- Page builder: fixed a blank live preview after editing a Style tab on a block with an entrance animation.
- Hero banner block: fixed Background Color not applying to the correct element.
- Fixed a page-view cache key collision that could serve a stale page render.

## [1.4.1] — 2026-08-01

### Added
- Free, self-hosted alternative to the paid Anthropic AI checker in LMS.

### Fixed
- AI checker score clamped to valid range; no longer builds/starts by default.
- Test suite no longer makes real network calls with the self-hosted checker enabled.
- Updated README, CLAUDE.md, and AGENTS.md to match the current codebase.
- Fixed self-hosted AI checker build and startup failures (PyTorch mismatch, dependency bump).
- Documented reduced reliability on very short or casual submissions.
- Fixed unnecessary Hugging Face network calls on startup.
- Routine dependency updates for the self-hosted AI checker, each verified with a build + smoke test.

## [1.4.0] — 2026-07-31

### Added
- Multi-language support for pages, menus, school info, staff, departments, and announcements.
- AI-assisted translation for website content and admin UI text.
- Automatic Bengali date and digit formatting on the public site.
- Bilingual (English + Bengali) sample data throughout.
- Translation status indicators on Staff, Department, Page, and Announcement lists.
- Sample data added for previously-empty modules (certificates, ID cards, SMS, payroll, loans, refunds, holidays, contact messages).
- Refreshed Bengali translations using real usage data.

### Fixed
- Admin language setting no longer affects what public visitors see.
- Fixed a crash browsing the admin panel in Bengali.
- Fixed AI translation scrambling menu order.
- Fixed several public-site details (dates, phone numbers, addresses, admission form) not translating.
- Fixed staff and announcement translations not showing on the public site.
- Fixed the AI-translate button closing the edit form unexpectedly.
- Fixed test failures and static-analysis warnings.
- Cleaned up ~30 incorrect or awkward Bengali translations.
- Fixed admission form labels not translating.
- Cleaned up duplicate translation entries.

### Changed
- Removed a redundant copy button superseded by AI-translate.

## [1.3.4] — 2026-07-31

### Added
- Wired up ~19 dormant site-theming settings via a new Advanced Theme section.
- Font choices validated against an allow-list.
- Redesigned public header into a sticky, shrinking bar with an Apply Now button and fluid type scale.
- Two new page-builder blocks: Announcement Bar and FAQ accordion.

### Changed
- Demo school now showcases the new theming, announcement bar, and FAQ blocks.

### Fixed
- Fixed the nav underline visually colliding with the dropdown arrow on submenu items.

## [1.3.3] — 2026-07-28

### Added
- Shared cPanel hosting support: local-disk fallback and a full deployment guide.
- Version integrity checking against git tags.
- Deployment script and VPS/cPanel update documentation.

### Changed
- Routine dependency bumps (Laravel framework/Pint, concurrently).

### Fixed
- Removed leftover debug logging from the Admission Form editor.
- Fixed `scripts/deploy.sh` not being executable after cloning.
- Fixed stale caching examples in the project docs.
- Fixed a remaining caching call that broke page rendering on shared hosting.
- Fixed the health-check endpoint hardcoding a Redis connection.
- Moved the app version to a git-tracked file.

### Security
- Patched a denial-of-service vulnerability in a dev-only dependency (`concurrently`).

## [1.3.2] — 2026-07-27

### Added
- Gallery Photo/Video blocks now open a lightbox with prev/next navigation.
- Hover/motion polish across the public site, respecting reduced-motion preferences.

### Changed
- Refreshed the public site's design tokens.
- School accent color now used in the nav underline.
- Restyled the homepage and page-builder blocks (visual only).

### Fixed
- Fixed the Admission Form block's width and a 500 error on pages using it.
- Fixed muted text and hardcoded strings bypassing translation.

## [1.3.1] — 2026-07-25

### Added
- Submit buttons show a spinner and disable while submitting.

### Changed
- Unified the admin panel's color system.
- Page headers support multiple action buttons.
- Shared badge component used across admin views.

### Removed
- Dead student-detail page and unused dark-mode CSS.

### Fixed
- Fixed ~250 lines of corrupted CSS from a bad copy-paste.

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
- Per-page SEO fields (meta title, description, Open Graph image) with Twitter Card tags.
- Duplicate Page and Save as Template actions, plus a template management screen.
- Media library with drag-and-drop upload, image picker, and alt-text editing.
- Autosave with local crash recovery and a conflict warning.
- Public page rendering now cached and invalidated automatically on publish.
- Reorganized the block editor's Layout tab into sub-sections.
- Accessibility improvements across the canvas.

### Changed
- Padding/margin controls moved to the Advanced tab as four-box controls.
- Block reordering is drag-only now.
- Media fields show a live thumbnail preview.
- Page editor sidebar now has a fixed minimum width.

### Fixed
- Registered a missing filesystem disk for the media library.
- Fixed inconsistent page-layout ordering when timestamps tie.
- Resolved 14 static-analysis errors.
- Fixed clipped status badges and a hidden Media Library modal in fullscreen mode.
- Fixed double-HTML-escaped page titles and meta tags.
- Fixed a skipped test file and two incorrect test assertions.

## [1.2.0] — 2026-07-24

### Added
- Elementor-style live page builder with fullscreen canvas and live preview.
- Click-to-select, drag-to-reorder, and copy/paste style on the canvas.
- Drag-and-drop block placement, including into nested containers.
- 8 new block types, including two nestable layout blocks.
- Per-block Style and Layout tabs, applied consistently across block types.
- Session undo/redo, page revision history, and copy/paste block style.
- Scheduled dependency updates via Dependabot.

### Changed
- Page editor sidebar is now resizable and remembers its width.
- Block tabs are smaller and more clearly show the active tab.

### Fixed
- Fixed a crash rendering pages with nested blocks.
- Fixed undo/redo dropping a nested block's children.
- Fixed the responsive viewport toolbar not resizing the live preview.
- Fixed an unclickable sidebar-resize divider.
- Removed dead legacy rich-text-editor code.
- Fixed a console error from the live preview.

### Security
- Rate-limited login and two-factor verification (5 attempts/minute).
- Password/2FA changes now sign out every other session.
- Email change requests notify the current address, with a way to cancel it.

## [1.0.1] — 2026-07-23

### Added
- Self-service Account & Security page: change name/password/email, enable 2FA, manage sessions.
- Placeholder favicon across every layout.
- Release version shown in the admin panel footer.

### Fixed
- Fixed selected language reverting to English after a page refresh.
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
