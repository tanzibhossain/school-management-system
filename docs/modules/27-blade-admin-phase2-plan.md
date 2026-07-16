# Blade Admin — Phase 2 Plan (remaining work)

**Status:** ✅ **Complete** · **Follows:** `27-blade-admin-plan.md` (Phase 1 — Setup/People/Finance/Academics/
Comms+Reports/optional modules, all ✅). This doc planned everything still missing after the Phase-1 audit.
All items are now **done**.

Two parts: **(A)** gaps inside the original plan's own scope, and **(B)** backend modules that had no admin
UI at all. Each item lists what to build, the **existing Services/Models to reuse**, gotchas, and a Feature-
test sketch. Everything follows the Phase-1 conventions: thin `Admin/{Area}` controllers → module Services →
Blade views (`page-header` + card + DataTables + BS modals), `routes/web.php` under `['auth','school']`,
one `tests/Feature/Admin/*Test.php` per area (SQLite, `RoleSeeder`, `actingAs` session).

## Conventions & gotchas learned in Phase 1 (apply to all items below)
- **Token-guarded services under session auth:** services that gate on `$user->tokenCan('admin:*')`
  (Attendance, Mark, and likely **Leave/Loan approvals**) see no Sanctum token in the web guard. Assign
  `$user->withAccessToken(new \Laravel\Sanctum\TransientToken())` before the call (its `can()` returns true) —
  see `AttendanceController`.
- **`QueryException extends RuntimeException`** — a broad `catch (RuntimeException)` around a Service swallows
  DB constraint violations into friendly flashes. Always assert `assertSessionHasNoErrors()` on the happy path
  so NOT-NULL/enum surprises surface in tests.
- **NOT NULL columns a lean form can miss** (seen repeatedly): supply every required column. Confirm each
  table's schema before writing the form.
- **Optional modules** are gated with `->middleware('module.enabled:{name}')` + a `@if (in_array(...))` sidebar
  entry. (None of Phase 2 are optional except Messaging.)
- **Route `->defaults('type', …)` + `{id}` bind positionally** — read the defaulted param via
  `$request->route()->parameter('type')`, never a method arg.
- Reuse module **Services** for every write; never `DB::table()` or new business logic in the controller.
- **Spatie `role:` multi-role syntax is PIPE-separated** — `role:admin|accountant`. A **comma**
  (`role:admin,accountant`) makes Spatie read the second value as the *guard* → "Auth guard [accountant] is
  not defined" 500. (Ability/permission middleware differ; role uses `|`.)

---

# Part A — Gaps inside the Phase-1 plan's scope

### A1. Role gating ✅ DONE (`tests/Feature/Admin/RoleGatingTest.php`)
Implemented: `routes/web.php` split into `role:admin,accountant` (Finance + Reports) and `role:admin`
(everything else); dashboard open to any staff; sidebar role-filtered (`$isAdmin`/`$canFinance`).
*Original note:* every `admin.*` route was only `auth`+`school`; any staff login would reach everything.
- **Build:** a `role:` middleware group. Wrap Finance + Reports in `role:admin,accountant`; keep the rest
  `role:admin` (there's already a real Spatie `role` middleware alias — used by Platform/Messaging APIs).
  Role-filter the sidebar with `@if ($u->hasAnyRole([...]))`.
- **Reuse:** existing `role` middleware alias (`bootstrap/app.php`), Spatie `hasRole`.
- **Gotcha:** the Blade admin is currently admin-only in practice; decide the matrix (admin = all; accountant =
  Finance/Reports + read-only People; others → their own later areas). Server check is the real guard.
- **Test:** an `accountant` user gets 200 on `/admin/invoices`, 403 on `/admin/students` (write) and
  `/admin/exams`.

### A2. Class routine editor ✅ DONE (`tests/Feature/Admin/RoutineAreaTest.php`)
Periods & rooms CRUD; per class/section weekly grid (periods × Mon–Fri) with conflict-checked add/remove
(`RoutineSchedulingService::hasConflict`). Under Setup sidebar. *Original scope below.*
- **Build:** per class+section weekly grid (periods × days → subject/room). Screens: routine index (pick
  class/section), grid editor.
- **Reuse:** `Academic\Services\RoutineSchedulingService`, `AcademicRepository::getRoutineForClass/getPeriods/
  getRooms`, models `ClassRoutine`, `RoutinePeriod`, `RoutineRoom`. Manage periods & rooms first (simple CRUD).
- **Gotcha:** routine has double-booking guards (room/teacher) that throw `422`/`ValidationException` — catch
  and surface. Largest single screen in Setup; consider a full page, not a modal.
- **Test:** create period + room, place a routine cell, assert the `class_routines` row; double-book → error.

### A3. Student detail tabs *(People)* ✅ DONE
- **Build:** `students/{id}` detail page with tabs: Academics (current + history), Guardians, Subjects
  (optional/4th), Invoices. Add "promote", "transfer", "link sibling" actions.
- **Reuse:** `StudentService::promote/transfer/reAdmit/linkSiblings`, `StudentRepository`, relations
  `currentAcademic`, `guardians`, `subjects`; Payment `Invoice` where `student_id`.
- **Test:** detail page renders tabs; promote moves the student's current academic; guardian add persists.

### A4. School opening-hours / weekend editor ✅ DONE (`SchoolHoursCreditTest.php`)
Per-day (is_open + open/close) editor card on the school-settings page (`PUT /admin/school/hours`,
`updateOrCreate` per weekday). *Original scope below.*
- **Build:** a card on the school-settings page to edit each `day_of_week` (is_open, open/close time). This
  drives Attendance working-days.
- **Reuse:** `SchoolService::updateOpeningHour`, `School->openingHours` (already eager-loaded, just unused).
- **Test:** toggle a day closed → `school_opening_hours` updated; attendance then treats it as non-working.

### A5. Student-credit ledger ✅ DONE (`SchoolHoursCreditTest.php`)
Finance screen: pick a student → balance + `CreditTransaction` history + manual credit/debit
(`CreditService::credit/debit`; debit throws `RuntimeException` on insufficient balance). *Original scope below.*
- **Build:** read-only view of a student's `StudentCredit` balance + `CreditTransaction` history; optional
  manual credit/debit.
- **Reuse:** `Payment\Services\CreditService::balance/credit/debit`, models `StudentCredit`,
  `CreditTransaction`.
- **Test:** credit a student, assert balance; it auto-applies on the next invoice (already covered by
  InvoiceService).

### A6. Messaging *(Comms — deferred in Phase 1)* ✅ DONE
- **Build:** threads inbox, thread view (poll `?after=`), compose (policy-gated participants), admin oversight
  (read + lock). Optional module (`module.enabled:messaging`).
- **Reuse:** `Messaging` module's `ThreadService`, `MessageService`, `MessagingPolicyService`,
  `MessagingModerationService`; REST-poll pattern.
- **Gotcha:** biggest single build here (interactive, polling). `role:admin` for oversight, not `ability:*`.
  Attachments via MinIO. Consider last, or a read-only admin-oversight subset first.

---

# Part B — Backend modules with no admin UI

Ordered by effort (small/self-contained first). All are **not** optional-gated (except none) and use
`role:admin` (Leave/Loan approvals are admin-only per the module specs — no manager field).

### B1. Leave ✅ DONE (`tests/Feature/Admin/LeaveAreaTest.php`)
Leave types CRUD + student/staff request lists with approve/reject/cancel (TransientToken bypass), under a new
HR sidebar section. *Original scope below.*
- **Screens:** Leave types CRUD; student leave requests list + approve/reject/cancel; staff leave requests
  list + approve/reject/cancel. (Submission also exists via portals; admin mainly approves.)
- **Reuse:** `LeaveTypeService`, `StudentLeaveService` + `StaffLeaveService` (`submit/approve/reject/cancel`),
  models `LeaveType`, `StudentLeaveRequest`, `StaffLeaveRequest`.
- **Gotcha:** approving a student leave overrides `absent`→`leave` via `WorkingDayService`; approvals may be
  token-gated → **TransientToken**. Staff approval is admin-only.
- **Test:** submit (or seed) a request → approve → status `approved`; reject with reason; can't approve twice.

### B2. Loan ✅ DONE (`tests/Feature/Admin/LoanAreaTest.php`)
Staff loans: create (submit on behalf), detail with repayment schedule, approve (generates schedule)/reject/
cancel — TransientToken; under HR sidebar. *Original scope below.*
- **Screens:** staff loans list + create (amount, installments → preview schedule) + approve/reject/cancel;
  loan detail with `LoanSchedule` rows.
- **Reuse:** `StaffLoanService::submit/approve/reject/cancel`, `LoanScheduleCalculator::calculateSchedule`,
  models `StaffLoan`, `LoanSchedule`.
- **Gotcha:** interest-free; repayment/paid-marking is deferred to **Payroll** (already integrated). Approvals
  may be token-gated → TransientToken.
- **Test:** create loan → schedule rows generated; approve → status `approved`.

### B3. Certificate ✅ DONE (`tests/Feature/Admin/CertificateAreaTest.php`)
Testimonial templates CRUD; issue testimonial (generate+issue, PDF via `render()`); admit-card generate
(MinIO) + download. New Certificates sidebar entry with tabs. *Original scope below.*
- **Screens:** Testimonial templates CRUD; issue a testimonial for a student (render PDF); generate an
  **admit card** for a student+exam (PDF). Transfer Certificate lives in the Student module (add to A3 detail).
- **Reuse:** `TestimonialTemplateService`, `TestimonialService::generate/render/issue`,
  `AdmitCardService::generate`, shared `App\Services\PdfRenderingService` (stream like the Reports area).
- **Gotcha:** DomPDF, no Blade views for the PDF body (services build HTML). Stream inline like
  `ReportController::stream`.
- **Test:** create template → issue testimonial → row + `certificate_path`; admit card generate for exam.

### B4. IdCard ✅ DONE (`tests/Feature/Admin/IdCardAreaTest.php`)
ID-card templates CRUD (layout/colors/visible fields); request batch (type/template/scope) → sync job renders
PDFs to MinIO in ≤200-card sheets; batch list/detail + per-file download. Under the Certificates &amp; IDs tab
group. *Original scope below.*
- **Screens:** ID-card templates CRUD; request a batch (student/staff, template, scope+filters) → shows batch
  status; download generated PDF.
- **Reuse:** `IdCardTemplateService`, `IdCardBatchService::request` (dispatches Horizon
  `GenerateIdCardBatchJob`), models `IdCardBatch`, `IdCardBatchFile`.
- **Gotcha:** first queued job in the app — under `QUEUE_CONNECTION=sync` the job runs inline and **must catch
  everything** (it does). 200-cards-per-PDF chunking; photos inlined as base64. Batch status is the signal.
- **Test:** request a batch → `id_card_batches` row created (status transitions handled by the job).

### B5. OnlineAdmission ✅ DONE (`tests/Feature/Admin/AdmissionAreaTest.php`)
Applications list + detail; approve (admission_number + section → `StudentService::enrol`) / reject; under
People sidebar. *Original scope below.*
- **Screens:** admission applications list (filter by status) + detail; approve (→ calls
  `StudentService::enrol`, needs class/section/year decision) + reject (reason). Public submit already exists.
- **Reuse:** `AdmissionApplicationService::approve/reject`, model `AdmissionApplication`.
- **Gotcha:** `approve()` enrols the student (capacity/plan-limit throws → catch); reference+phone status check
  is a public endpoint (not admin).
- **Test:** seed an application → approve with class/section/year → `students` row created; reject sets status.

### B6. DataImport ✅ DONE (`tests/Feature/Admin/DataImportAreaTest.php`)
Student/staff Excel/CSV upload (`ImportBatchService::request` → MinIO + sync job); batch list with counts +
detail with per-row errors. Under People sidebar. *Original scope below.*
- **Screens:** upload a student or staff Excel/CSV → creates an `ImportBatch`; list batches with row
  counts/errors; view per-row errors (JSON).
- **Reuse:** `ImportBatchService::request` (dispatches the Horizon import job reading MinIO via
  maatwebsite/excel; reuses `StudentService::enrol`/`StaffService::hire` per row).
- **Gotcha:** needs real file upload (multipart) + MinIO; queued job. Errors stored as JSON on the batch.
  Provide a template download.
- **Test:** post a small CSV → `import_batches` row; (row processing is the job's concern).

### B7. Website *(large — its own milestone)*
- **Screens:** Pages (CRUD, layout editor, publish/versioned revisions, set homepage, templates), Menus +
  items, Site settings, Site layout (header/footer), Media library, Notices.
- **Reuse:** `PageService` (`create/update/saveLayout/publish/duplicate/restore/setHomepage/saveAsTemplate`),
  `MenuService::replaceItems`, `SiteSettingService`, `SiteLayoutService`, `WebsiteMediaService`,
  `PageTemplateService`. Public rendering is `PublicPortalService` (separate public Blade site, later).
- **Gotcha:** `layout_json` is an **opaque versioned blob** — every save is a new `PageLayout` row. This is a
  page-builder; scope carefully (start with Pages + Menus + Site settings; defer a visual builder).
- **Test:** create page → save layout (new revision) → publish; menu replaceItems.

---

## Suggested build order
1. **A1 Role gating** (small, security) →
2. **B1 Leave** → **B2 Loan** (small, share the request→approve pattern) →
3. **A4 Opening hours** + **A5 Credit ledger** (small Setup/Finance fills) →
4. **B5 OnlineAdmission** → **B3 Certificate** (PDF) →
5. **A2 Class routine** + **A3 Student detail tabs** (medium) →
6. **B4 IdCard** + **B6 DataImport** (queued jobs + uploads) →
7. **B7 Website** (large, own milestone) →
8. **A6 Messaging** (each a separate sub-app).

**✅ ALL ITEMS COMPLETE** — Every item above has been implemented with controllers, views, routes, and tests.

Each item = one `Admin/{Area}` controller set + views + `routes/web.php` group + one `*Test.php`, exactly like
Phase 1. Update the milestone table in `27-blade-admin-plan.md` as each lands.
