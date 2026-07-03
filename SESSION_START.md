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
| 16 | Report | `app/Modules/Report` | No new models — pure aggregation over Payment's schema — 🔶 code complete 2026-07-04, awaiting Docker test run |
| 17 | Sms | `app/Modules/Sms` | SmsBatch, SmsLog — 🔶 code complete 2026-07-04, awaiting Docker test run |
| 18 | DataImport | `app/Modules/DataImport` | ImportBatch — 🔶 code complete 2026-07-04, awaiting Docker test run |

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

## Module 16: Report — code complete 2026-07-04, awaiting Docker test run

**Depends on:** Payment (#8), Student (#4) — both complete. Mark (#11) is listed as a CLAUDE.md dependency
but ISN'T actually touched by this pass — see scope note below.

CLAUDE.md had no spec for this module beyond the dependency line and an escalation warning ("the Report
module's cross-module aggregations" → escalate to Fable 5 if a specific piece proves hard). The DevPlan docx
has no dedicated Report section at all, and v1's `old/app/Modules/Report` is built entirely against a v1-only
accounting subsystem (`Cashbank`, `OthersPayment`, `SalesChart` — a full chart-of-accounts layer v2 never
built) plus Payroll/SMS-era reports — none of that schema exists in v2, so it wasn't usable as prior art.
Scope was confirmed with the user via three Q&A decisions: build Fee Collection + Outstanding Dues + Student
Ledger only (NOT the "Combined Student Report Card" that would have actually touched Mark — so despite the
CLAUDE.md dependency line, this pass is Payment+Student only; Mark integration is explicitly deferred, not
forgotten), JSON + streamed PDF export with no new package (no Excel/CSV support added), and no caching —
reports read already-persisted Payment data live.

### What was built
- **No new tables or models** — this module is a pure read/aggregation layer over Payment's existing schema
  (`invoices`, `invoice_items`, `payments`, `refunds`, `student_credits`, `credit_transactions`) joined to
  `students`/`student_academics`/`classes`/`sections` for class/section context. Steps 1, 2, and 5 of the
  usual 10-step pattern (migrations, models, observers) are genuinely empty here — nothing to migrate,
  nothing new to cache-flush — and were skipped rather than manufactured.
- `ReportRepository` — plain class (NOT extending `BaseRepository`, which is built around cache-aside reads
  for a single Eloquent model) built on `DB::table()` joins rather than Eloquent relations, since
  invoices/payments reference `student_id`/class data without DB-level FKs (the same cross-module convention
  used everywhere else in this schema) — there are no Eloquent relations to lean on anyway.
- **Fee Collection Report** (`GET /v2/reports/fee-collection`) — active (non-reversed) payments in a date
  range, joined to the invoice's class/section via the student's academic record **for that invoice's own
  `academic_year_id`** (not "current" class — so a report run later still reflects the class the student was
  in when the fee was paid), filterable by class/section/method, totals grouped by currency and by method.
- **Outstanding Dues Report** (`GET /v2/reports/outstanding-dues`) — invoices in `unpaid`/`partial` status,
  remaining amount computed as `amount_due − amount_paid − credit_applied`, grouped per student with an
  oldest-due-date and per-invoice breakdown, filterable by class/section/academic_year.
  Paid/waived/cancelled invoices are excluded.
- **Student Financial Ledger** (`GET /v2/reports/students/{studentId}/ledger`) — a merged, sorted timeline of
  that student's invoices/payments/refunds/credit-transactions. Deliberately does NOT compute one invented
  "running balance" mixing invoice-due and credit-wallet semantics — entries just carry their own type/amount,
  and the summary object reports `total_invoiced`, `total_paid`, `total_refunded`, `current_outstanding` (from
  currently unpaid/partial invoices), and `credit_balance` (from `student_credits`) as distinct figures.
- **PDF export** — `?format=pdf` on all three endpoints, reusing the existing `PdfRenderingService::renderToPdf()`
  but **streamed directly in the HTTP response, not stored to MinIO and not logged to any DB row** — unlike
  Certificate/IdCard, a report is a live filter-driven snapshot, not an official document with its own history
  worth keeping. `ReportPdfBuilder` builds the HTML (plain tables, no DB-stored template — same reasoning as
  AdmitCard's fixed layout).
- **Access**: all three endpoints are `admin:*,accountant:*` only (route middleware + FormRequest
  `authorize()` both enforce it) — not teacher-accessible, matching Payment/Loan's convention for
  financial data.
- Tests: `tests/Feature/Report/{FeeCollectionReportTest,OutstandingDuesReportTest,StudentLedgerReportTest}.php`
  — date-range filtering, class/section scoping (against the invoice's own academic year, not current),
  reversed-payment exclusion, multi-invoice aggregation per student, refund/credit-transaction inclusion in
  the ledger, PDF export (`%PDF` header check, same pattern as Certificate/IdCard), access control, auth.

### Known gaps / follow-ups
- No PHP available to run `php artisan test` in this session — run the Docker test command below before merging.
- Mark integration (the "Combined Student Report Card" joining academic result + fee status) was explicitly
  deferred by the user, not attempted — CLAUDE.md's Mark dependency for this module isn't yet exercised.
- No Excel/CSV export (no package was added) — JSON + PDF only.
- No caching layer — every report read hits Payment's tables live. Fine at current expected data volumes;
  revisit if a school's full-history reports get slow.

---

## Module 17: Sms — code complete 2026-07-04, awaiting Docker test run

**Depends on:** Student (#4), Payment (#8) — both complete.

CLAUDE.md's only spec was the encoding-aware cost note. A real contradiction surfaced during design: the
DevPlan docx explicitly states SMS/email are billed at a single **platform-level** account ("schools do NOT
have their own SMS accounts"), and v1's actual code backs that up (one global `sms_api`/`sms_sid` pair in
`config/app.php`) — but the already-built School module (#1) has per-school `sms_api_key`/`sms_sender_id`
columns sitting unused. Confirmed with the user: **per-school credentials** (using those existing columns),
which meant this module's only migration work was adding the missing piece (`sms_cost_per_segment`) rather
than a full config table. Two more decisions: a stub `LogGateway` behind a real `SmsGatewayContract` interface
(no SMS provider package exists in composer.json, no live credentials to test against either way — swapping
in a real provider later means implementing the interface, nothing else changes), and — after the user asked
"can this go on a queue so it doesn't get stuck processing" — every send request (1 recipient or 500) goes
through Horizon via `SendSmsBatchJob`, mirroring IdCard's batch pattern exactly, rather than sending
synchronously in the request.

### What was built
- **Schools migration**: adds `sms_cost_per_segment` (nullable decimal) alongside the pre-existing
  `sms_api_key`/`sms_sender_id` — completes the per-school SMS config set. Wired into School's model
  fillable/casts, `SchoolResource`, and `UpdateSchoolRequest`.
- `sms_batches` (school_id, purpose enum(manual|due_reminder), scope enum(single|class|all) + class_id/
  section_id/academic_year_id/target_ids — same targeting shape as `id_card_batches`, message_body nullable
  (only used for purpose=manual — due_reminder computes a personalized body per recipient instead), status,
  total_count, error_message, requested_by nullable/nullOnDelete, completed_at)
- `sms_logs` (school_id, batch_id, student_id, guardian_id nullable/nullOnDelete, recipient_phone
  denormalized, body, encoding enum(gsm7|unicode), segment_count, cost nullable — null when
  `sms_cost_per_segment` isn't configured, status enum(sent|failed), error_message, purpose, sent_by
  nullable/nullOnDelete, **resent_from_id** self-referencing nullable FK — a resend creates a NEW row
  pointing at the original rather than mutating failed history, sent_at)
- **`SmsSegmentCalculator`** (`app/Modules/Sms/Services`) — pure, unit-tested class (mirrors Loan's
  `LoanScheduleCalculator`). Detects GSM 03.38 basic/extended character sets; ANY character outside that
  alphabet (Bangla, emoji, accented Latin not in the GSM extension table) forces the whole message to
  unicode. Segment math uses the real telecom thresholds: GSM-7 is 160 chars single-part but only
  **153/part concatenated** (7 septets reserved for the UDH header); unicode is 70 single-part but
  **67/part concatenated** (3 UCS-2 units reserved). Extended-table GSM-7 characters (`{}[]|\^~€`) cost
  2 septets each, not 1, since they need an escape sequence.
- **`SmsGatewayContract`** (interface) + **`LogGateway`** (stub, bound in `AppServiceProvider::register()`)
  — records that a send was attempted without any network call. The one realistic failure mode (no guardian
  phone on file) is caught in `SmsBatchService::sendAndLog()` BEFORE the gateway is ever invoked — logged as
  `failed` with an explicit error message, never silently skipped.
- **`SendSmsBatchJob implements ShouldQueue`** — resolves targets (student list for manual, students-with-
  unpaid/partial-invoices for reminders), and for each one calls `SmsBatchService::sendAndLog()` — the single
  choke point that resolves the primary guardian's phone (Student itself has no phone column — matches how
  v1 also texted guardians, not students), computes segments/cost, calls the gateway, and writes the SmsLog
  row. Same non-rethrowing exception handling as IdCard's job (batch row carries the failure signal), same
  `QUEUE_CONNECTION=sync`-runs-inline behavior in tests.
- **Due reminders** are NOT a stored template — the message is composed per-recipient from
  `resources/lang/en/sms.php`'s `due_reminder` key (`:student`/`:amount`/`:currency` placeholders) — the
  first lang file in v2, per the Global Product Rules' "translation keys, never hardcoded" requirement.
  Amount is aggregated directly against Payment's `invoices` table (NOT via the Report module — each module
  stands alone, no cross-module service dependency).
- **Resend** re-sends the exact original body verbatim to a freshly re-resolved phone number, creating a new
  `sms_logs` row with `resent_from_id` pointing at the original — implemented as a direct synchronous call
  through `sendAndLog()`, not re-queued (a single retry doesn't need batching).
- **Access**: manual sends `admin:*,teacher:*` (a teacher texting their own class is normal); due reminders
  `admin:*,accountant:*` (financial trigger, matches Report's gating); batch/log history and resend are
  admin-only.
- Tests: `tests/Unit/Sms/SmsSegmentCalculatorTest.php` (GSM-7/unicode boundary math, extended-character
  cost, Bangla/emoji forcing unicode) + `tests/Feature/Sms/{SmsManualTest,SmsDueReminderTest,SmsResendTest,
  SmsBatchHistoryTest}.php` (class/single scope targeting, missing-phone failure logging, due-reminder
  amount/currency rendering, resend creating a new row with the right `resent_from_id`, access control, auth).

### Known gaps / follow-ups
- No PHP available to run `php artisan test` in this session — run the Docker test command below before merging.
- No real SMS provider is wired up — `LogGateway` is a stub. Swapping one in means writing a class that
  implements `SmsGatewayContract` and rebinding it in `AppServiceProvider::register()`; nothing else changes.
- No per-school SMS template system (a "SMS templates with placeholders" feature was explicitly not scoped
  for this pass) — manual sends are always free-typed text.
- `sms_cost_per_segment` must be configured per school for `cost` to be populated on logs; it stays `null`
  otherwise (segments are still always computed regardless).

---

## Module 18: DataImport — code complete 2026-07-04, awaiting Docker test run

**Depends on:** Student (#4), Academic (#2) per CLAUDE.md's dependency line — Staff (#5) is also reused since
the user chose to include teacher import, even though the build-order table's dependency list doesn't name it.

CLAUDE.md had no dedicated spec for this module — the only mention lives inside the DevPlan docx's "13.11
School Onboarding" section: student + teacher import via Excel upload, queued job, an import report (success
count, skipped rows, per-row errors with row numbers), and downloadable sample files. v1's old `DataImport`
module turned out to be unrelated (a ZKTeco fingerprint-attendance importer with a staging-table + manual
per-cell-edit UI, Bootstrap-MVC era) — not reused, though its "let a human fix bad rows before committing"
idea was considered and explicitly rejected in favor of the simpler one-pass flow the DevPlan actually asked
for. Three decisions confirmed with the user: student **and** teacher import (not student-only), one-pass
validate-and-insert with a report rather than a staging/review UI, and student rows carry one primary
guardian's fields rather than being a bare enrollment-only sheet.

### What was built
- **`import_batches`** — the only new table. `type` enum(student|staff), `status` enum(queued|processing|
  completed|failed), `original_filename`, `stored_path` (MinIO — the queued job needs to read the file back
  after the request ends), `total_rows`, `success_count`, `skipped_count`, **`errors` as a JSON column**
  (array of `{row, messages}`) rather than a child table like Sms's per-recipient logs — an import error
  isn't individually actionable (no per-row "resend"), it's read-only report output bounded by the file's
  row count. `error_message` is separate and only used for a whole-file failure (e.g. an unreadable file).
- **Row processing reuses the existing services rather than duplicating create logic**: each row is resolved
  and validated by `StudentImportRowService`/`StaffImportRowService`, then handed to the *same*
  `StudentService::enrol()` / `StaffService::hire()` the normal API controllers call — so an imported student
  or teacher is created under identical business rules (ID generation, section capacity checks) instead of a
  second code path that could drift out of sync.
- Text → ID resolution (no composite lookup exists on the Academic/Staff models): `class_name`/`section_name`
  matched by exact name scoped to `school_id` (section further scoped to the resolved class); `academic_year`
  resolved by year value if given, else falls back to the school's current year; `designation_name`/
  `department_name` (staff only) resolved the same way. Any unresolved name, missing required field, invalid
  enum value (gender/blood_group/guardian_relation/employment_type), unparseable date, or duplicate
  `admission_number` throws `RowImportException` (array of messages) — caught per-row by the job, which
  increments `skipped_count` and appends `{row, messages}` to the batch's `errors` array, then **continues to
  the next row** rather than aborting the batch.
- **`ImportBatchJob implements ShouldQueue`** — same shape as `GenerateIdCardBatchJob`/`SendSmsBatchJob`:
  downloads the stored file from MinIO to a local temp path (`maatwebsite/excel`'s `Excel::import()` needs a
  real path, not a cloud-disk stream), reads it via a small `RowCollectionImport` (`ToCollection` +
  `WithHeadingRow` — headers auto-slug to snake_case, e.g. "Admission Number" → `admission_number`, so the
  downloadable template's human headers map straight onto the row-service field names with no manual column
  mapping step), loops rows with a per-row try/catch, and only a whole-file exception (outer try/catch, same
  swallow-don't-rethrow reasoning as IdCard/Sms) marks the batch itself `failed`. Temp file always cleaned up
  in a `finally` block.
- **Sample templates**: `StudentImportTemplateExport`/`StaffImportTemplateExport` (`FromArray` + `WithHeadings`,
  one example row each) served via `GET /v2/data-imports/template?type=student|staff`.
- **Access**: admin-only across the board (upload, history, template download) — bulk data creation is
  high-risk, same posture as IdCard's template management.
- Dates: Excel cells can arrive as either a numeric Excel serial date (real date-formatted cell) or a plain
  text string (CSV, or a text-formatted cell) — a small `ParsesExcelDates` trait handles both rather than
  assuming one, using `PhpOffice\PhpSpreadsheet\Shared\Date` for the numeric case and `Carbon::parse()` for text.
- Tests: `tests/Feature/DataImport/{DataImportStudentTest,DataImportStaffTest}.php` — build a *real* .xlsx via
  PhpSpreadsheet directly (not `UploadedFile::fake()`, which has no parseable content) so the job's
  `Excel::import()` call exercises genuine spreadsheet bytes end-to-end under `QUEUE_CONNECTION=sync`. Covers
  successful student import with a guardian, unknown-class row skipped and reported, same-file duplicate
  admission numbers (first row succeeds, second is skipped since the check runs after the first row's insert
  commits), staff import with designation/department resolution, invalid gender / unknown designation
  skipped and reported, batch history, template download, and access control.

### Known gaps / follow-ups
- No PHP available to run `php artisan test` in this session — run the Docker test command below before merging.
- No staging/review UI — a row with any error is simply skipped and reported; there's no way to fix and
  re-submit just the bad rows short of re-uploading a corrected file.
- Staff import doesn't include class/subject teaching assignment (`StaffService::assign()`) — matches how
  manual hiring via the API also treats that as a separate step, out of scope here.
- No sibling-linking or address import for students — only the fields in CLAUDE.md's row-field decision
  (core student fields + one primary guardian). Multiple guardians, addresses, and sibling links must be
  added afterward via the normal Student API.

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
feat(data-import): description   # current module
fix(data-import): description
test(data-import): description
```

## Run & Ship (full checklist in CLAUDE.md)

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test tests/Feature/Leave/ tests/Unit/Loan/ tests/Feature/Loan/ tests/Feature/Certificate/ tests/Feature/Student/TransferCertificateTest.php tests/Feature/IdCard/ tests/Feature/Report/ tests/Unit/Sms/ tests/Feature/Sms/ tests/Feature/DataImport/ --no-coverage

git checkout dev && git pull origin dev
git checkout -b feature/data-import-module
# ... commits ...
git checkout dev
git merge --no-ff feature/data-import-module
git push origin dev
git branch -d feature/data-import-module
```

Note: Report has no migrations (no new tables); Sms and DataImport both do (Sms: schools.sms_cost_per_segment
+ sms_batches/sms_logs; DataImport: import_batches only) — run migrate before either module's tests.
