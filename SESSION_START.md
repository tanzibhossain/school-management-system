# Session Start — School Management System v2

Paste this file content at the start of every new Cowork or Claude Code session.
Switch model to Fable 5 first: click the model picker → select **Claude Fable 5**.

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

## What Is Next — Module 12: Leave

**Depends on:** Student (#4), Staff (#5) — both complete. Integrates with Attendance (#10).

### What the Leave module needs to do
- Leave types per school (sick, casual, etc.) with per-year day limits — applies to students AND staff
- Leave requests: date range, reason, optional attachment, status workflow (pending → approved/rejected, cancellable)
- Day count = WORKING days only (reuse `WorkingDayService` — excludes weekends + holidays)
- **Attendance integration (already spec'd in CLAUDE.md)**: approved student leave auto-sets attendance status `leave` for those dates, overriding an existing `absent`
- Approval: Head Teacher/admin approves; teachers can approve their own section's student requests (reuse `class_teacher_id` pattern)
- Leave balance tracking per person per year against the type's limit

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
