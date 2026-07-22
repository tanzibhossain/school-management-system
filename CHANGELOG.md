# Changelog

All notable changes to this project are documented here. Format loosely
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versioning
follows [Semantic Versioning](https://semver.org/).

## [Unreleased]

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

### Added
- `.github/dependabot.yml` for scheduled composer/npm/docker/github-actions
  dependency updates.

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

[1.0.1]: https://github.com/tanzibhossain/school-management-system/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/tanzibhossain/school-management-system/releases/tag/v1.0.0
