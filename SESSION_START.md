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
| 12 | Leave | `app/Modules/Leave` | LeaveType, StudentLeaveRequest, StaffLeaveRequest — 🔶 code complete 2026-07-03, awaiting test run in Docker |

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

## Architecture Rules (summary — full rules in CLAUDE.md)

- Module path: `app/Modules/{ModuleName}/` — 10-step pattern, one commit per step
- All queries scoped to `school_id` — via `app('current_school_id')`
- Controllers thin (max 40 lines/method) — logic in Services
- Every write endpoint: FormRequest with `authorize()` + `rules()`; every response: JsonResource
- Cache: `Cache::tags([...])->remember()` in Repositories; Observers flush on saved/deleted
- Middleware: `['auth:sanctum', 'ability:admin:*,teacher:*']` as appropriate
- Tests: SQLite in-memory — add `protected $table` if pluralisation is wrong; mirror DB defaults in `$attributes`
- **Test gotchas learned so far**: Sanctum guard caches the user within a test — call `$this->app['auth']->forgetGuards()` when switching tokens; use `whereDate` not `whereBetween` for date-range queries (SQLite stores date casts as datetime); fresh-model resources return 201 — force 200 on lazily-created GET endpoints

---

## Git Convention

```
feat(leave): description   # current module
fix(leave): description
test(leave): description
```

## Run & Ship (full checklist in CLAUDE.md)

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test tests/Feature/Leave/ --no-coverage

git checkout dev && git pull origin dev
git checkout -b feature/leave-module
# ... commits ...
git checkout dev
git merge --no-ff feature/leave-module
git push origin dev
git branch -d feature/leave-module
```
