# Changelog

All notable changes to this project are documented here. Format loosely
follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/); versioning
follows [Semantic Versioning](https://semver.org/).

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

[1.0.0]: https://github.com/tanzibhossain/school-management-system/releases/tag/v1.0.0
