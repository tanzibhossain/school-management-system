# 16 — Report

**Status:** ✅ Done · **Depends on:** Payment, Mark, Student · **Path:** `app/Modules\Report`

## Scope
This module provides financial and academic reporting, including fee collection summaries, outstanding dues, student ledger reports, and exportable PDF/JSON reports.

## Tables
No new tables are introduced; the module aggregates existing data for reporting.

## API Endpoints
- Admin and accountant-only report endpoints for:
  - fee collection overview
  - outstanding dues
  - student ledger reporting
- JSON and streamed PDF export endpoints

## Services & Business Rules
- Reports are generated from Payment, Mark, and Student data without introducing new persistence layers.
- Access is restricted to admin and accountant roles.
- Report generation is intentionally non-cached.

## Integration Points
- Pulls from Payment for fee collections and dues.
- Pulls from Mark for academic performance summaries.
- Pulls from Student for ledger and student-level reporting.
