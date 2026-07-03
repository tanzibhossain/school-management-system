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
| 12 | Leave | `app/Modules/Leave` | LeaveType, StudentLeaveRequest, StaffLeaveRequest — ✅ tests green (2026-07-03, after guard-caching + SQLite date-format fixes) |
| 13 | Loan | `app/Modules/Loan` | StaffLoan, LoanSchedule — ✅ tests green 2026-07-03 |
| 14 | Certificate | `app/Modules/Certificate` | AdmitCard, TestimonialTemplate, Testimonial — ✅ tests green 2026-07-03 (incl. Transfer Certificate PDF retrofit) |
| 15 | IdCard | `app/Modules/IdCard` | IdCardTemplate, IdCardBatch, IdCardBatchFile — 🔶 code complete 2026-07-03, awaiting Docker test run |

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

## Module 14: Certificate — code complete 2026-07-03, awaiting Docker test run

**Depends on:** Student (#4), Mark (#11) — both complete.

CLAUDE.md had no agreed spec for this module either. The DevPlan's combined "Modules 13–19" prompt table
says: `Certificate | TemplateCompilationService | Use DomPDF. Admit card + transfer certificate + testimonial.
Needs Mark data.` — but **Transfer Certificate already exists**, fully built inside the Student module
(`app/Modules/Student/{Models,Services,Http}/...TransferCertificate*`, with its own template table). So this
module's real scope is just Admit Card + Testimonial, confirmed with the user via three Q&A decisions:
implement real DomPDF rendering (dompdf was installed but unused — even the existing TC only ever produced
HTML, never a stored PDF), Testimonial = conduct remark + academic summary from Mark, Admit Card allows a
"To be announced" placeholder when exam seating isn't assigned yet.

### What was built
- **Shared**: `App\Services\PdfRenderingService` (top-level, alongside `BaseService`/`BaseRepository` — not
  inside any one module, since Certificate depends on Student, not the reverse). Wraps
  `barryvdh/laravel-dompdf`: `renderToPdf()`, `store()`, `generateAndStore()`.
- `admit_cards` (school_id, student_id, exam_id, file_path, generated_at, generated_by) — no DB-stored
  template (content is a structured schedule/seating table, not prose); HTML built directly in
  `AdmitCardService` from `Exam`/`ExamSubject` (schedule) and `ExamSeating`/`ExamHallSeat` (hall/seat, falls
  back to "To be announced" if seating hasn't been assigned for that exam yet). `generate()` upserts by
  (school_id, student_id, exam_id) — regenerating updates, never duplicates.
- `testimonial_templates` / `testimonials` — mirrors Transfer Certificate's exact pattern: per-school HTML
  template with `{{placeholders}}`, one `is_default`. `TestimonialService::render()` substitutes
  `{{grade}}`/`{{gpa}}`/`{{percentage}}` from Mark's `ExamResult` (only when an `exam_id` is given — a
  testimonial can be a pure conduct reference with none) and `{{attendance_percentage}}` from
  `AttendanceService::studentSummary()` (only when an explicit `attendance_from`/`attendance_to` range is
  given — deliberately NOT inferred from `academic_years`, which has no start/end dates; guessing calendar
  bounds would bake in a BD-style assumption). `generate()` creates a draft (no PDF yet); `issue()` renders +
  stores the PDF and flips status to `issued` — mirrors TC's own draft→issued lifecycle.
- **Transfer Certificate retrofit**: `TransferCertificateService` now takes `PdfRenderingService` and actually
  renders/stores a PDF in `issue()` (previously `file_path` was never written — the service's own docblock
  said PDF generation was "deferred... when MinIO + PDF skill is wired"; that gap is now closed for all three
  document types uniformly).
- Routes: `/v2/certificates/admit-cards/*` (admin+teacher), `/v2/certificates/testimonials/*` +
  `/testimonial-templates/*` (admin only)
- Tests: `tests/Feature/Certificate/{AdmitCardTest,TestimonialTest}.php` +
  `tests/Feature/Student/TransferCertificateTest.php` (new — TC had no test coverage before this). All PDF
  assertions use `Storage::fake('minio')` and check the stored bytes start with `%PDF` (a real DomPDF render
  executed, not a stub) rather than parsing PDF content.

### Known gaps / follow-ups
- No PHP available to run `php artisan test` in this session — run the Docker test command before merging.
- Admit Card has no per-school customizable template (unlike Testimonial/TC) — fixed layout, by design
  (content is tabular, not prose). Revisit if schools want branded admit cards later.
- Testimonial's academic summary only pulls ONE exam's result (admin picks which) — no auto-detection of
  "latest completed academic year," since `AcademicYear` has no date range to reason about that safely.

---

## Module 15: IdCard — code complete 2026-07-03, awaiting Docker test run

**Depends on:** Student (#4), Staff (#5) — both complete.

CLAUDE.md had no agreed spec for this module. The DevPlan docx only covers a "13.12 Student ID Card"
section (template layouts, per-school customisation, dompdf render "8 cards per A4 with crop marks",
bulk generation as a queued Horizon job) — no Staff ID Card spec exists there, and v1's `old/app/Modules/IdCard`
also had Guardian ID cards built on a generic pre-module `contacts` table, neither of which was trustworthy
to mirror directly. Design was confirmed with the user via four Q&A decisions: bulk generation runs as a
real queued Horizon job (this codebase's first — every other PDF module renders synchronously in-request),
one shared `id_card_templates` table with a `type` column (student|staff) rather than two parallel tables,
Guardian cards excluded (no Guardian module exists in v2), and only 2 of the DevPlan's 5 layouts
(`horizontal_classic`, `vertical`) are actually coded — the other 3 are valid config values with no
migration needed when they're built later. A follow-up decision capped each rendered PDF at 200 cards,
auto-splitting larger batches into multiple files rather than one unbounded PDF.

### What was built
- `id_card_templates` (school_id, type enum(student|staff), name, layout enum(5 values — only
  horizontal_classic/vertical rendered), background_color, accent_color, logo_path, font enum(sans|serif|mono),
  visible_fields json nullable, is_default)
- `id_card_batches` (school_id, type, template_id nullable/nullOnDelete, scope enum(single|class|all),
  class_id/section_id nullable — student-only, target_ids json nullable for scope=single, total_count,
  status enum(queued|processing|completed|failed), error_message, requested_by nullable/nullOnDelete,
  generated_at)
- `id_card_batch_files` (school_id, batch_id, file_index, file_path, card_count) — one row per rendered PDF
  chunk; a 3-student batch produces 1 row, a 450-student "all students" batch produces 3 (200/200/50)
- **`GenerateIdCardBatchJob implements ShouldQueue`** — the first queued job in the codebase. Resolves the
  target student/staff set via `IdCardBatchService::targetQuery()` (shared with the controller's up-front
  `total_count`), chunks it into groups of 200, renders each chunk through `IdCardRenderer` + the shared
  `PdfRenderingService`, and writes one `id_card_batch_files` row per chunk. On any exception the batch is
  marked `failed` with `error_message` — **not rethrown**, since the batch row's status is the client-facing
  failure signal (per the polling design) and rethrowing would propagate into the HTTP request that
  dispatched it under `QUEUE_CONNECTION=sync` (tests, and any deployment without Horizon configured — the
  job still runs, just inline, so no fake-queue plumbing was needed to test it).
- **dompdf doesn't fetch remote URLs by default** — photos and the template logo are read from MinIO and
  inlined as base64 `data:` URIs (`IdCardBatchService::resolveDataUri()`), not linked by signed URL. Logo and
  school phone number are resolved once per batch, not once per card.
- **Print sheet layout uses a `<table>`, not CSS grid** — dompdf's CSS support is table-layout-first and
  doesn't reliably support `display: grid` (or flexbox in the print sheet, though the per-card layouts do use
  flex, which dompdf supports). `IdCardRenderer::wrapSheet()` lays cards into a 2-column `<table>` instead.
- Routes: `/v2/id-cards/templates/*` (admin only), `/v2/id-cards/batches` (admin + teacher for
  `type=student`; `RequestIdCardBatchRequest::authorize()` narrows `type=staff` to admin-only, matching
  Testimonial's HR-sensitive-output precedent)
- Tests: `tests/Feature/IdCard/{IdCardTemplateTest,IdCardBatchTest}.php` — template CRUD + default-swap,
  class-scope batch, single-scope batch, staff batch, 205-student batch asserting a `[200, 5]` file split,
  class-scope rejected for staff type (422), teacher allowed for student batches but forbidden for staff
  ones, auth.

### Known gaps / follow-ups
- No PHP available to run `php artisan test` in this session — run the Docker test command below before merging.
- Guardian ID cards excluded per the Q&A decision — v1 had them (via `student_guardians` now), can be added
  later as a third `type` reusing the same template/batch infrastructure.
- Only 2 of 5 DevPlan layouts are coded (`horizontal_classic`, `vertical`); `horizontal_modern`, `dual_stripe`,
  `minimal` fall back to `horizontal_classic` in `IdCardRenderer::render()` until built.
- No batch cap validation beyond the 200-per-file chunking — a genuinely huge school (thousands of students)
  in `scope=all` would still take a while to render even split across files; revisit if that's reported slow.

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
feat(idcard): description   # current module
fix(idcard): description
test(idcard): description
```

## Run & Ship (full checklist in CLAUDE.md)

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test tests/Feature/Leave/ tests/Unit/Loan/ tests/Feature/Loan/ tests/Feature/Certificate/ tests/Feature/Student/TransferCertificateTest.php tests/Feature/IdCard/ --no-coverage

git checkout dev && git pull origin dev
git checkout -b feature/idcard-module
# ... commits ...
git checkout dev
git merge --no-ff feature/idcard-module
git push origin dev
git branch -d feature/idcard-module
```
