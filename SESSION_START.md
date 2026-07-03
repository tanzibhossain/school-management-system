# Session Start — School Management System v2

Paste this file content at the start of every new Cowork or Claude Code session.

**Model: Claude Sonnet 5 by default** (`/model sonnet`). The specs are locked in CLAUDE.md and
11 modules exist as reference patterns — follow them exactly, do not redesign.
Escalate to Fable 5 ONLY for: a test failure unsolved after 2–3 attempts, or the marked
complex modules (Report #16 aggregations, Payroll #21 salary math). Use Haiku for renames,
Pint runs, and doc-only edits.

**CLAUDE.md is the single source of truth** — specs, global product rules, build order,
and the Run & Ship checklist all live there. Where anything conflicts with CLAUDE.md, CLAUDE.md wins.

---

## Project Location

Backend:  `D:\dev\school-management-backend`  (Laravel 13 · PHP 8.3)
Frontend: `D:\dev\school-management-main`     (Next.js 15 — not started yet)

Run everything in Docker:
```bash
docker compose exec app php artisan <command>
```

---

## What Is Already Built (Modules 1–11)

| # | Module | Folder | Key Models |
|---|--------|--------|-----------|
| 1 | School | `app/Modules/School` | School (locale settings, country_code, institution_code), SchoolPhone, SchoolOpeningHour |
| 2 | Academic | `app/Modules/Academic` | AcademicYear, SchoolClass, Section (+class_teacher_id), Subject, SubjectRelation, AcademicGroup, Version, Shift, ClassRoutine |
| 3 | User / Auth | `app/Modules/User` | User + Sanctum + Spatie roles |
| 4 | Student | `app/Modules/Student` | Student, StudentAcademic, **StudentSubject** (enrollment + is_optional) |
| 5 | Staff | `app/Modules/Staff` | Staff (rfid_number) |
| 6 | Announcement | `app/Modules/Announcement` | Announcement |
| 7 | FeeItem | `app/Modules/FeeItem` | FeeCategory, FeeItem, FeeDiscount |
| 8 | Payment | `app/Modules/Payment` | Invoice, Payment (multi-currency), Refund, StudentCredit, PaymentConfig (per-school gateway creds), gateways declare SUPPORTED_CURRENCIES |
| 9 | Examination | `app/Modules/Examination` | ExamType, Exam, ExamSubject (+combined_group), ExamHall, ExamSeating |
| 10 | Attendance | `app/Modules/Attendance` | StudentAttendance, StaffAttendance, AttendanceSetting, Holiday — ✅ tests green |
| 11 | Mark | `app/Modules/Mark` | MarkDivision, MarkSetting, GradeBoundary, Mark, ExamResult, ExamWeight — 🔶 tests fixed, confirm green then merge |
| 12 | Leave | `app/Modules/Leave` | LeaveType, StudentLeaveRequest, StaffLeaveRequest — 🔶 code complete 2026-07-03, test failures fixed (guard-caching + SQLite date-format), re-run to confirm green |
| 13 | Loan | `app/Modules/Loan` | StaffLoan, LoanSchedule — 🔶 code complete 2026-07-03, awaiting test run in Docker |

### Key Attendance Details (module 10)
- Student attendance = once-daily status enum (present|absent|late|half_day|leave), bulk upsert per class/section
- Staff attendance = punch-based; RFID endpoint (first punch = in, last = out); auto clock-out job `attendance:auto-close` (every 30 min, per-school policy + timezone, check_out = school closing time, flagged `is_auto_closed`)
- Working days = school_opening_hours (weekend config) + `holidays` table ('closure' type = retroactive void day)
- Edit window 7 days (teacher) / unlimited (admin), audited via `edited_by`
- `WorkingDayService` is reusable — Leave module should use it for day counting

### Key Mark Details (module 11)
- Result strategies per class: `bd_national` (optional bonus, 5.00 cap, fail-one-fail-all), `simple_average`, `weighted_average`, `percentage_only`
- Templates in `config/grading.php` (grade boundaries + division sets) — seed data, not code
- Combined subjects via `exam_subjects.combined_group`; absent = "Ab" (never zero); N/A for non-enrolled
- Grace marks: separate audited column, per-class cap; merit rank always computed, `show_merit_position` toggle controls exposure
- Moderator lock: `exam_results.is_locked` + `marks.locked_at` — locked rows never recomputed
- Year-end weighted result: `exam_weights` + `GET /v2/marks/results/annual`
- NO cache on mark writes; tabulation cached under `Cache::tags(['tabulation'])`

---

## Module 12: Leave — code complete 2026-07-03, awaiting Docker test run

**Depends on:** Student (#4), Staff (#5) — both complete. Integrates with Attendance (#10).

### What was built
- `leave_types` (school_id, name, applies_to enum(student|staff|both), max_days_per_year nullable, requires_attachment, is_paid nullable, is_active)
- `student_leave_requests` / `staff_leave_requests` — split tables (mirrors Attendance's student/staff split), each with from_date/to_date, `working_days` snapshotted at submission, status enum(pending|approved|rejected|cancelled), requested_by/approved_by/approved_at/rejection_reason
- Day count = `WorkingDayService::countWorkingDays()` (reused from Attendance, not reimplemented)
- **Balance**: computed live from `SUM(working_days) WHERE status='approved'` — no separate ledger table; checked at submission AND re-checked under `lockForUpdate()` at approval (closes the race where two pending requests both pass the submission-time check)
- **Attendance integration**: `StudentLeaveService::approve()` walks each working day in range and sets `StudentAttendance.status = 'leave'` — creates the row if none exists, overrides only if the existing status is `absent`; present/late/half_day/leave are left untouched. Runs inside the same `DB::transaction` as the approval.
- **Approval authority**: student leave — class teacher of the request's section (via `sections.class_teacher_id`, same pattern as Attendance) or admin. Staff leave — **admin only**, since `Staff` has no manager/line-supervisor field to delegate to (flagged as a gap, not solved).
- **Cancellation**: requester may cancel while pending; admin may cancel pending or approved. Cancelling an approved request does NOT revert already-synced attendance rows — that needs a normal attendance correction (mirrors Mark's "never silently recompute locked results" caution).
- Staff leave has NO attendance sync — `StaffAttendance` is punch-based (check_in/check_out), not a daily status enum, so there's nothing to override.
- Routes: `/api/v2/leave/types` (admin), `/api/v2/leave/students/*` (admin+teacher), `/api/v2/leave/staff/*` (submit: admin+staff; approve/reject/pending: admin only)
- Tests: `tests/Feature/Leave/` — `LeaveTypeTest`, `StudentLeaveRequestTest`, `StaffLeaveRequestTest` (working-day counting incl. holiday + closed-weekday exclusion, attachment requirement, balance enforcement, class-teacher-only approval, attendance override behavior, cancellation, auth)

### Known gaps / follow-ups
- No PHP available to run `php artisan test` in this session — run the Docker test command below before merging.
- Staff leave approval delegation (line manager) deferred — admin-only for now.

### Global product reminders
- All user-facing strings via translation keys (English default)
- Dates in school timezone (pattern established in Attendance)
- No BD assumptions in core — day limits and types are per-school config

---

## Module 13: Loan — code complete 2026-07-03, awaiting Docker test run

**Depends on:** Staff (#5) — complete. No dependency on Payroll (#21, optional/unbuilt).

CLAUDE.md had no agreed spec for this module — the DevPlan docx (`docs/SchoolMS_v2_DevPlan_original.docx`,
"Module 12 — Loan") and the v1 reference code (`old/app/Modules/Loan`) disagreed with each other
(interest-bearing, penalty tables, settlement fees, built on a generic pre-module `contacts` table) and
weren't trustworthy to mirror directly, so the design below was confirmed with the user before building
(see the four Q&A decisions: interest-free, defer repayment tracking, request→approve workflow, admin+accountant access).

### What was built
- `staff_loans` (school_id, staff_id, requested_amount, installment_count, reason, start_date, status
  enum(pending|approved|rejected|cancelled), requested_by/approved_by/approved_at/rejection_reason) —
  same shape as Leave's request tables
- `loan_schedules` (school_id, staff_loan_id, installment_number, due_date, amount, is_paid, paid_amount,
  paid_at) — the last three columns are reserved for a future Payroll integration and are NOT written to yet
- **Interest-free**: `LoanScheduleCalculator::calculateSchedule(amount, installmentCount)` — pure, unit-tested
  class that splits the amount evenly across installments; the LAST installment absorbs any rounding
  remainder so the sum always exactly equals the requested amount. No amortization/interest math (deliberately
  simpler than the DevPlan's `AmortizationCalculationEngine`).
- **Workflow**: submit (staff themselves, or admin/accountant on their behalf) → admin/accountant approve or
  reject. Approval IS the disbursement moment — it generates the `loan_schedules` rows (monthly cadence from
  `start_date`, via `addMonthNoOverflow()`). No separate disburse step.
- **Repayment marking deferred**: no service/controller logic exists for marking an installment paid — that's
  explicit future scope once the Payroll module defines how salary deductions work.
- **Cancellation**: requester may cancel while pending; admin/accountant may cancel pending OR approved.
  Cancelling an approved loan also deletes its schedule rows (safe — nothing can be marked paid yet, so
  there's no repayment history to lose).
- **Access**: submit + view + self-cancel — `admin:*,accountant:*,staff:*`; approve/reject/pending queue —
  `admin:*,accountant:*` only (matches FeeItem's convention, not Payment's admin-only one — per user decision).
- Routes: `POST/GET /v2/loans/{staffId}`, `GET /v2/loans/pending`, `PATCH /v2/loans/{id}/approve|reject|cancel`
- Tests: `tests/Unit/Loan/LoanScheduleCalculatorTest.php` (even split, rounding-remainder-on-last-installment,
  single installment, invalid input) + `tests/Feature/Loan/StaffLoanTest.php` (submit→approve schedule
  generation, accountant can approve, staff cannot approve own request, reject, cancel incl. schedule cleanup,
  auth) — written with the Sanctum guard-caching (`forgetGuards()`) fix already applied from the Leave rework.

### Known gaps / follow-ups
- No PHP available to run `php artisan test` in this session — run the Docker test command before merging.
- No loan-type/eligibility-cap entity (unlike Leave's `LeaveType`) — amount is admin/accountant discretion,
  no automatic cap against `staff.basic_salary`. Flagged, not solved.
- Repayment/installment-paid tracking is a placeholder pending Payroll (#21).

---

## Architecture Rules (summary — full rules in CLAUDE.md)

- Module path: `app/Modules/{ModuleName}/` — 10-step pattern, one commit per step
- All queries scoped to `school_id` — via `app('current_school_id')`
- Controllers thin (max 40 lines/method) — logic in Services
- Every write endpoint: FormRequest with `authorize()` + `rules()`; every response: JsonResource
- Cache: `Cache::tags([...])->remember()` in Repositories; Observers flush on saved/deleted
- Middleware: `['auth:sanctum', 'ability:admin:*,teacher:*']` as appropriate
- Tests: SQLite in-memory — add `protected $table` if pluralisation is wrong; mirror DB defaults in `$attributes`
- **Test gotchas learned so far**: Sanctum guard caches the user within a test — call `$this->app['auth']->forgetGuards()` EVERY time a test switches to a different user's token (missing this caused 3 silent false-pass/false-fail auth bugs in Leave's first test run); use `whereDate` not `whereBetween` for date-range queries, and use the full `Y-m-d H:i:s` string (not just `Y-m-d`) in `assertDatabaseHas` against a `date`-cast column (SQLite stores it as datetime); fresh-model resources return 201 — force 200 on lazily-created GET endpoints

---

## Git Convention

```
feat(loan): description   # current module
fix(loan): description
test(loan): description
```

## Run & Ship (full checklist in CLAUDE.md)

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test tests/Feature/Leave/ tests/Unit/Loan/ tests/Feature/Loan/ --no-coverage

git checkout dev && git pull origin dev
git checkout -b feature/loan-module
# ... commits ...
git checkout dev
git merge --no-ff feature/loan-module
git push origin dev
git branch -d feature/loan-module
```
