# CLAUDE.md — School Management System v2

Claude Code reads this file automatically at the start of every session.
Follow every rule here without exception across all 25 modules.

---

## Project Overview

Multi-tenant SaaS school management platform.
Stack: Laravel 13 · PHP 8.3 · MySQL 8 · Redis 7 · Laravel Horizon · MinIO · Sanctum · Spatie Permission

## Frontend Architecture (decided 2026-07-04, not yet built)

Frontend work has not started (backend modules #23–25 still pending). Decided ahead of time so the eventual
build doesn't improvise:

- **Three Next.js 15 apps in one monorepo** (Turborepo-style: `apps/marketing`, `apps/school-site`, `apps/dashboard`,
  shared UI/components package). Not three separate repos, not one merged app.
- **`apps/marketing`** — single-tenant vendor site: features, pricing, contact form, demo request. Not
  school-specific, not behind `ResolveSchool`.
- **`apps/school-site`** — per-school public site, consumes the Website module's (#20) `/public/*` endpoints
  (pages, menus, notices, admission form, results checker, staff list, stats).
- **`apps/dashboard`** — per-school logged-in app (admin/teacher/student/parent) consuming every other module's API.
- **Tenant routing: subdomain per school** — e.g. `{school}.yourapp.com` for the public site,
  `app.{school}.yourapp.com` (or `/app` under the same subdomain) for the dashboard. School resolution on the
  frontend reads the subdomain and forwards it so Laravel's `ResolveSchool` middleware can resolve
  `current_school_id` the same way it does today — no backend change needed for this, just confirm
  `ResolveSchool` accepts a school-identifying header/param the frontend can set from the subdomain.
- **Known gap**: the marketing site's "demo request" / contact form has no backend endpoint yet — none of the
  25 modules cover lead capture for the vendor itself (as opposed to a school's own admission applications).
  Needs a small addition (new lightweight module, or extend Announcement) before `apps/marketing` can go live.
  Not built yet — flagged for when frontend work starts.

## Model Policy

- **Default: Claude Sonnet 5.** All specs in this file are final — build by mirroring the 11 existing modules; do NOT redesign schemas, strategies, or conventions.
- **Escalate to Fable 5 only for**: a test failure still unsolved after 2–3 attempts; the Report module's cross-module aggregations; Payroll's salary calculations. Escalate the specific problem, not the whole module.
- **Haiku 4.5** for renames, formatting, docblock and status-table edits.
- When in doubt about a design question, the answer is in this file or in an existing module — search before asking, and never invent a new pattern.


---

## Architecture Rules

- Every module lives in `app/Modules/{ModuleName}/`
- Each module has the following structure:
  ```
  app/Modules/{ModuleName}/
  ├── Http/
  │   ├── Controllers/
  │   ├── Requests/
  │   └── Resources/
  ├── Models/
  ├── Repositories/
  ├── Services/
  ├── Observers/
  ├── database/
  │   └── migrations/
  └── routes/
      └── api.php
  ```
- **Controllers must be thin** — max 40 lines per method. All business logic goes in Services.
- Every write endpoint requires a `FormRequest` with `authorize()` and `rules()`.
- Every API response must use a `JsonResource` class. Never return a Model directly.
- Repositories use `Cache::tags([...])->remember()` — see `StudentRepository` for the pattern.
- Observers flush cache tags on `saved()` and `deleted()`.
- Financial writes (Payment module) always use `DB::transaction()`. No cache on write operations.
- Use Sanctum ability middleware: `middleware(['auth:sanctum', 'ability:admin:*'])`
- Every table must have a `school_id` column (except platform-level tables: schools, plans).
- All queries must be scoped to `school_id`. Never return cross-school data.
- The current school is available via `app('current_school_id')` (set by `ResolveSchool` middleware).

---

## Naming Conventions

| Type | Convention | Example |
|------|-----------|---------|
| Module folder | PascalCase | `Student`, `Payment`, `Academic` |
| Service | `{Domain}Service.php` | `StudentService`, `BillingService` |
| Repository | `{Domain}Repository.php` | `StudentRepository` |
| Resource | `{Model}Resource.php` | `StudentResource` |
| Collection | `{Model}Collection.php` | `StudentCollection` |
| Store request | `Store{Model}Request.php` | `StoreStudentRequest` |
| Update request | `Update{Model}Request.php` | `UpdateStudentRequest` |
| Observer | `{Model}Observer.php` | `StudentObserver` |
| Migration | Laravel default timestamp prefix | `2026_07_01_create_students_table.php` |

---

## Module Build Order

Build in dependency order — never start a module before its dependencies are complete.

| # | Module | Depends On | Status |
|---|--------|-----------|--------|
| 1 | School | — | ✅ done |
| 2 | Academic | School | ✅ done |
| 3 | User / Auth | — | ✅ done |
| 4 | Student | Academic, User | ✅ done |
| 5 | Staff | Academic, User | ✅ done |
| 6 | Announcement | — | ✅ done |
| 7 | FeeItem | Academic | ✅ done — `app/Modules/FeeItem` (FeeCategory, FeeItem, FeeDiscount) |
| 8 | Payment | Student, FeeItem | ✅ done — Invoice, Payment, Refund, StudentCredit, CreditTransaction, PaymentConfig, PaymentGatewayLog |
| 9 | Examination | Academic, Student | ✅ done — ExamType, Exam, ExamSubject, ExamHall, ExamHallSeat, ExamSeating; anti_adjacency seating + blank_every |
| 10 | Attendance | Student, Staff | ✅ done 2026-07-02 — `app/Modules/Attendance` (StudentAttendance, StaffAttendance, AttendanceSetting, Holiday); tests green |
| 11 | Mark | Examination, Attendance, Student | ✅ done 2026-07-02 — `app/Modules/Mark` + `student_subjects` prerequisite; 4 result strategies, templates in `config/grading.php`; tests green |
| 12 | Leave | Student, Staff | ✅ done 2026-07-03 — `app/Modules/Leave` (LeaveType, StudentLeaveRequest, StaffLeaveRequest); approved student leave overrides `absent`→`leave` via WorkingDayService; staff approval admin-only (no manager field yet); tests green |
| 13 | Loan | Staff | ✅ done 2026-07-03 — `app/Modules/Loan` (StaffLoan, LoanSchedule); interest-free advances, request→approve workflow like Leave, admin+accountant decide; repayment/installment marking deferred until Payroll (#21) exists; tests green |
| 14 | Certificate | Student, Mark | ✅ done 2026-07-03 — `app/Modules/Certificate` (AdmitCard, TestimonialTemplate, Testimonial); Transfer Certificate stays in the Student module (not duplicated) but was retrofitted to actually generate PDFs; shared `App\Services\PdfRenderingService` wraps DomPDF; tests green |
| 15 | IdCard | Student, Staff | ✅ done 2026-07-03 — `app/Modules/IdCard` (IdCardTemplate, IdCardBatch, IdCardBatchFile); first queued-job module (Horizon `GenerateIdCardBatchJob`, sync in tests), 200-cards-per-PDF chunking, base64-inlined photos/logos for dompdf; tests green |
| 16 | Report | Payment, Mark, Student | ✅ done 2026-07-04 — `app/Modules/Report` (no new tables — pure aggregation over Payment's schema); Fee Collection, Outstanding Dues, Student Ledger reports; JSON + streamed PDF (`?format=pdf`, no MinIO storage); admin+accountant only; tests green |
| 17 | Sms | Student, Payment | ✅ done 2026-07-04 — `app/Modules/Sms` (SmsBatch, SmsLog); per-school billing (School.sms_api_key/sms_sender_id/sms_cost_per_segment); GSM-7/unicode-aware `SmsSegmentCalculator` (160/153 septets, 70/67 unicode); stub `LogGateway` behind `SmsGatewayContract` (no real provider wired); queued `SendSmsBatchJob` (Horizon, same pattern as IdCard); manual bulk SMS + due reminders + resend; tests green |
| 18 | DataImport | Student, Academic | ✅ done 2026-07-04 — `app/Modules/DataImport` (ImportBatch only, no per-row child table — errors stored as JSON on the batch); student **and** staff/teacher import in scope (Staff module reused even though not in this row's dependency list); one-pass validate-and-insert with a report (success/skipped counts + per-row `{row, messages}` errors), not a staging-table review UI; each row calls the *existing* `StudentService::enrol()` / `StaffService::hire()` directly rather than duplicating create logic; class/section/academic-year/designation/department resolved from text by name, scoped to `school_id`; uploaded sheet stored in MinIO before the queued job (Horizon `ImportBatchJob`, same swallow-don't-rethrow pattern as IdCard/Sms) reads it back via `maatwebsite/excel` (already a dependency); downloadable sample templates at `GET /v2/data-imports/template?type=`; admin-only; tests green |
| 19 | OnlineAdmission | Academic, Student | ✅ done 2026-07-04 — `app/Modules/OnlineAdmission` (AdmissionApplication — its own table, never a half-formed Student row); public unauthenticated `POST /v2/admission-applications` (throttled) + public `GET .../status?reference=&guardian_phone=` (reference number alone is guessable, so phone must also match); no `section_id` captured at application (placement decided at approval, when capacity is known); `approve()` takes `admission_number` (never auto-generated anywhere in this codebase) + `section_id` and calls the *existing* `StudentService::enrol()` in the same action — same reuse pattern as DataImport; admin-only for review/approve/reject (the DevPlan's "moderator" role/ability was never actually built — real roles are `super_admin, admin, teacher, accountant, librarian, receptionist, student, parent` per `RoleSeeder`, and every other module gates on `admin:*`); status notifications (SMS/email) explicitly deferred; tests green |
| 20 | Website | — | ✅ done 2026-07-04 — `app/Modules/Website` (Page, PageRedirect, PageLayout, SiteLayout, SiteSetting, Menu, MenuItem, PageTemplate, WebsiteMedia — full DevPlan Sprint-1 backend scope, 9 tables); layout stored as an opaque `layout_json` LONGTEXT blob on both `page_layouts` and `site_layouts` (Next.js/Craft.js owns block structure, Laravel never parses it); every layout save is a NEW row (`const UPDATED_AT = null;`, never an update) — versioned revisions, `PageService::restore()` copies an old row's json into a new row rather than rewinding; slug change on `PageService::update()` auto-creates a `page_redirects` row inside `DB::transaction()`; `MenuService::replaceItems()` is delete-all-then-recreate for the whole tree (one level of nesting only — grandchildren rejected in `ReplaceMenuItemsRequest::withValidator()`); `SiteSetting` is a one-row-per-school singleton via `forSchool()`/`firstOrCreate` (mirrors `AttendanceSetting`); admin:* only for all `/v2/website/*` write endpoints; public unauthenticated `/public/*` routes (`throttle:60,1`) serve dynamic blocks — Notice Board reuses `AnnouncementRepository::listVisible()` as-is, Staff/Teacher List filters by `designation_id`/`department_id` (documented gap: no subject-relation filter, Staff has none), Class Routine joins `class_routines`, Stats Counter counts active students/staff, and Result Checker is entirely NEW code inside `PublicPortalService::checkResult()` (roll-number + exam lookup, only returns `is_locked=true` results, published exams only — Mark's own `ExamResultController` was NOT touched); tests in `tests/Feature/Website/` (Page, Menu, SiteSetting, SiteLayout, PageTemplate, WebsiteMedia, PublicPortal); tests green |
| 21 | Payroll *(optional)* | Staff | ✅ done 2026-07-04 — `app/Modules/Payroll` (SalaryComponent, StaffSalaryValue, PayrollRun, PayrollEntry, SalaryCertificateRequest); gated `admin:*,accountant:*` (the DevPlan's "Finance"/"Head Teacher" roles don't exist in `RoleSeeder`, same resolution as every other module); default earning/deduction components (`config/payroll.php`, seed data not logic) lazily seeded per school on first access, editable/trashable (never hard-deleted, so historic breakdowns stay meaningful); calculation is flat component sums only (`gross = Σearnings`, `net = gross − Σdeductions`) — no attendance proration, matching the DevPlan's `calculateGrossAndNet` exactly; `PayrollEntry.breakdown` JSON snapshots every earning/deduction/loan line at process time (mirrors Mark's snapshot-marks-at-entry-time rule) so later component/loan edits never silently alter an already-processed run; `PayrollService::processRun()` is idempotent (reprocessing a still-draft run wipes and regenerates entries, like Attendance's daily register) and pulls due, unpaid `LoanSchedule` installments in as an extra deduction line, marking them `is_paid`/`paid_amount`/`paid_at` on `approveRun()` — fulfilling what Loan's own docblocks said they were waiting for; no endpoint transitions a run to `paid` (the DevPlan's own ROUTES list never defines one — documented gap); payslips/salary certificates render via the shared `PdfRenderingService` (same heredoc-HTML-string pattern as Certificate, no Blade views); self-service (`GET /v2/payroll/staff/me/payslips`, `/me/certificates`, `POST /salary-certificate`) required fixing a pre-existing bug in `User::abilitiesForRole()` — Sanctum's `tokenCan()` is a literal string match, so `ability:staff:*`/`teacher:*` route gates never actually matched a real login's narrow-scoped abilities (only hand-crafted test tokens passed); fixed by adding matching wildcards (`teacher:*`, `staff:*`, `accountant:*`, etc.) per role, which also retroactively un-breaks the same latent gap in Leave and Loan's staff self-service routes; school_module_settings/`CheckModuleEnabled` toggle (DevPlan's generic optional-module gate) deliberately deferred — better designed once, for all of Payroll/LMS/Library/Transport/Messaging together; tests in `tests/Feature/Payroll/` (SalaryComponent, StaffSalary, PayrollRun incl. loan-deduction integration, Payslip, SalaryCertificate); tests green 2026-07-04 (after module.enabled:payroll retrofit) |
| 22 | LMS *(optional)* | Academic, Student | ✅ done 2026-07-04 — `app/Modules/LMS` (Course, Lesson, Assignment, Submission, SubmissionAiCheck); real Anthropic API integration (not stubbed — see decisions below); gated by the new `school_module_settings` toggle (retrofitted onto Payroll too); tests green after fixing missing `$table` declarations, wrong exception class, and job exception handling |
| 23 | Platform | — | 🔶 code complete 2026-07-04 — `app/Modules/Platform` (Plan, PendingSchoolSignup, SubscriptionReminder); adds `subdomain` + plan/subscription fields to `schools`; Stripe checkout (raw Http-facade calls, no SDK, mirrors AnthropicAiChecker), webhook-driven provisioning idempotent on retry, Super Admin offline creation + plan changes gated by a NEW `role:super_admin` Spatie-role middleware (NOT `ability:`, since `admin` tokens' bare `*` would otherwise satisfy any ability gate — see notes below), signed-URL "set password" email (no plaintext passwords), demo school reset job (`platform:demo-reset`, 00:00/14:00 cron ≈ every 14h), daily subscription reminder job; `PlanLimitService` hook added to `StudentService::enrol()`/`StaffService::hire()` (shared-file edits); tests in `tests/Feature/Platform/`; awaiting Docker test run |
| 24 | Library *(optional)* | Student, Staff | ⬜ pending |
| 25 | Transport *(optional)* | Student, Payment | ⬜ pending |
| 26 | Messaging *(optional)* | User | ⬜ pending |

**Prerequisite before Mark:** add `student_subjects` table (school_id, student_id, subject_relation_id, academic_year_id, is_optional) to the Academic or Student module — required for optional (4th) subjects, N/A handling, and teacher mark-entry scoping.

---

## The 10-Step Pattern

Every module is built in exactly this order. Each step = one commit.

1. Migration(s)
2. Model (fillable, casts, relationships, scopes)
3. Repository (extends BaseRepository, Redis cache-aside)
4. Service (extends BaseService, business logic)
5. Observer (cache flush on saved/deleted)
6. FormRequests (Store + Update)
7. Resource + Collection
8. Controller + routes/api.php
9. Feature tests + unit tests
10. Pint formatting + docblock cleanup

---

## DO NOT

- Use `DB::table()` directly in controllers or services — always use the Repository
- Return Eloquent models directly from API endpoints — always use a Resource
- Put business logic in controllers
- Use Laravel Passport (not installed — Sanctum only)
- Cache financial or mark-entry write operations
- Skip the `school_id` scope on any query
- Use `127.0.0.1` for DB/Redis inside Docker — use service names (`db`, `redis`)

---

## Global Product Rules

V2 is a global product. V1 was Bangladesh-only — do NOT carry BD assumptions into core code.

- **School locale settings** (School module settings): `currency`, `timezone`, `locale`, `academic_year_pattern`, weekend days. BD values (BDT, Asia/Dhaka, Fri+Sat weekend) are a seed template, never hardcoded.
- **Multi-currency payments**: `currency` column on schools, invoices, and payments (done 2026-07-02).
- **Gateway policy**: availability is by school country — Bangladesh: bKash + SSLCommerz only; all other countries: Stripe + PayPal. Each school enters its OWN gateway credentials (`payment_configs` is per school — pattern already in place). More country-specific gateways will be added later, so gateways stay behind a common contract: each declares `SUPPORTED_CURRENCIES` (pattern in place) and, when built, a gateway registry maps `schools.country_code` (ISO alpha-2, added 2026-07-02) → available gateways. Never hardcode a gateway choice in billing logic — `PaymentService` guards currency before every gateway call.
- **Grading templates**: school picks a template during setup — `bd_national_5.0`, `us_letter_4.0`, `uk_9_1`, `percentage_only`. Template seeds `grade_boundaries`; Head Teacher can edit per class afterward.
- **Result strategy pattern**: result/GPA calculation is a pluggable strategy per class (like seating strategies): `bd_national`, `simple_average`, `weighted_average`, `percentage_only`. BD-specific rules (optional-subject bonus, 5.00 cap, fail-one-fail-all) live only inside the `bd_national` strategy.
- **Language**: English default, full multi-language support via Laravel lang files. All user-facing strings (validation, SMS templates, notices) through translation keys — never hardcoded.
- **No BD-only fields in core**: institution code is generic (label configurable; "EIIN" is just the BD label). Addresses are flexible free-form fields — no BD geo tables.
- **Scope**: primary market is BD schools class 3–10 + college (HSC 11–12; groups already supported). Degree-level credit-hour systems are out of scope for now — keep ExamType flexible.

---

## Attendance Module — Agreed Spec (Module 10)

- **Student attendance** = once-daily status per student (no clock-out): enum `present | absent | late | half_day | leave`. Bulk upsert per class/section — resubmitting the register updates, never errors. Unique (school_id, student_id, date).
- **Staff attendance** = punch-based: check_in, check_out, `source` enum(manual|rfid), `is_auto_closed` boolean.
- **Auto clock-out**: scheduled job runs after each school's closing time (school timezone). Open records get check_out = that day's closing time from `school_opening_hours` (NEVER job run time), `is_auto_closed = true`. Auto-closed hours never count toward payroll/overtime without approval. Policy per school: close_at_closing_time (default) | max_shift_hours | off. Clock-out with no clock-in = flagged incomplete, never invent a check-in.
- **Tables**: `student_attendances` (school_id, student_id, class_id, section_id, academic_year_id, date, status, note, recorded_by, edited_by nullable), `staff_attendances` (school_id, staff_id, date, check_in, check_out, status, source, is_auto_closed), `attendance_settings` (school_id, auto_close_policy, max_shift_hours, edit_window_days, late_threshold_minutes).
- **Working-day aware**: attendance only on working days (per-school weekend config + holidays). Attendance % denominator = working days within the student's enrollment period (mid-year admissions count from admission date). Retroactive school closure: a "void day" mechanism excludes an already-marked date from all % calculations.
- **Corrections**: editable within `edit_window_days` (default 7) by the recording teacher; older edits require Head Teacher ability. Every edit stores `edited_by` (audit).
- **Leave integration (module 12)**: approved leave auto-sets status `leave` for those dates, overriding an existing `absent`. `leave` counts as excused — excluded from the absent count, configurable whether it counts in the % denominator.
- **Mark integration (module 11)**: attendance-division marks are SNAPSHOTTED at mark-entry time (stored in `marks` like any entered value). Later attendance edits never silently change computed exam results.
- **RFID**: device endpoint accepts raw punches; first punch of day = check_in, last = check_out, intermediate punches ignored. Dates are school-local (school timezone), never UTC-derived.
- **Timezone rule**: "today" is always resolved in the school's timezone — one server, many countries.
- **No cache on attendance write operations** (high-frequency daily writes).

---

## Mark Module — Agreed Spec (Module 11)

Decisions reconciled from v1 code + DevPlan + review (2026-07-02). Where the DevPlan docx conflicts with this section, THIS section wins.

- **Grade defaults (bd_national template)**: A+ 80–100 (5.00), A 70–79 (4.00), A− 60–69 (3.50), B 50–59 (3.00), C 40–49 (2.00), D 33–39 (1.00), F 0–32 (0.00). The DevPlan's 4.0-scale defaults are WRONG.
- **Divisions per exam subject** (not per class): `mark_divisions` — school_id, exam_id, exam_subject_id, name, max_marks, pass_mark (nullable), display_order. Subject-level pass mark already exists on `exam_subjects.pass_marks`.
- **Tables**: `mark_divisions`, `mark_settings` (school_id, class_id, mode enum(mark|grade), result_strategy), `grade_boundaries` (school_id, class_id, grade_label, min_percent, max_percent, gpa_point), `marks` (school_id, exam_id, student_id, mark_division_id, marks_obtained, is_absent, entered_by, locked_at), `exam_results` (school_id, exam_id, student_id, total_marks, percentage, grade, gpa, is_pass, merit_position, is_locked).
- **Persist results**: `exam_results` rows are written on calculation and locked after Moderator approval — no recompute-on-read for locked results. Tabulation view cached via `Cache::tags(['tabulation'])`, flushed by MarkObserver.
- **Must support** (all existed in v1): absent handling (`is_absent`, display "Ab", absent ≠ zero), optional/4th subject with GPA bonus (bd_national: GPA = (Σ compulsory GP + max(0, optional GP − 2.00)) / compulsory count, cap 5.00), combined subjects (e.g. Bangla 1st + 2nd paper graded as one with combined pass mark), merit position with tie handling (GPA → total → percentage; failed ranked after passed), N/A for non-enrolled subjects.
- **Division templates (decided 2026-07-02)**: ready-made mark-division sets a school can apply per exam subject — e.g. `standard` (Attendance 10 / Mid 30 / Final 60), `continuous` (Attendance / Assignment / Class Test / Mid / Final) — or fully custom divisions. Templates are seed data, not code.
- **Exam weighting (decided 2026-07-02)**: year-end combined result configurable per school/class — weighted aggregation across exams (e.g. Half-Yearly 30% + Annual 70%) via `exam_weights` config; ready-made templates + custom. Schema included in Mark v1.
- **Merit rank visibility (decided 2026-07-02)**: always computed and stored; per-school setting `show_merit_position` controls exposure to students/parents. BD template default: visible.
- **Grace marks (decided 2026-07-02)**: separate audited `grace_marks` column on `marks` (never mixed into `marks_obtained`), `grace_given_by` audit, per-school cap in mark settings. Applied before pass/grade calculation.
- **Re-exams/improvement (decided 2026-07-02)**: DEFERRED. Keep ExamType flexible so a retake exam type can reference an original exam later.
- **No cache on mark write operations** (same rule as Payment).

---

## Platform Module — Agreed Spec (Module 23)

Added 2026-07-04 — not in the original 25-module list. The DevPlan docx had a "§13.10 SaaS Plans & Demo
Mode" + "§13.11 School Onboarding" section (a "Super Admin Portal," DevPlan roadmap Weeks 26–27) that was
silently dropped when CLAUDE.md superseded the DevPlan. It resurfaced because the marketing site
(`apps/marketing`) needs a real "buy a package → get logged in" flow, which needs backend support that has
never existed: nothing in this codebase creates a School + admin User together, no `plans` table exists
despite CLAUDE.md's schema rule already carving out an exception for one, and no vendor-side billing exists
at all (Payment module's gateways are entirely about a school billing ITS OWN students, never about billing
a school for the platform itself). Confirmed with the user via three rounds of decisions — see below.

**This module is platform-level, not tenant-level.** Most of it does NOT go through `ResolveSchool`/
`current_school_id` scoping — Super Admin endpoints operate across every school, and the public
signup/checkout endpoints run before a school even exists yet. This is a new precedent (every module before
this one assumed an already-resolved `current_school_id`).

### Plans (seed data, editable later by Super Admin — these are placeholder defaults, not final pricing)
| Plan | Price | Caps | Notes |
|---|---|---|---|
| Demo | Free, not purchasable | 20 students / 10 staff | The ONE shared `is_demo=true` school; login credentials are shown prefilled on a public demo login page (no signup flow); resets every **14 hours** (DevPlan said 24h — user overrode to 14h) |
| Trial | Free, 30 days | 100 students / 15 staff | Self-serve signup, no payment, all optional modules (`school_module_settings`) left off by default like any new school |
| Basic | $19/mo or $190/yr | 500 students / 40 staff | Self-serve Stripe Checkout |
| Pro | $49/mo or $490/yr | Unlimited | Self-serve Stripe Checkout |

### Schema
- `plans` (platform-level, no `school_id` — same exception CLAUDE.md already carves out for `schools`): name,
  slug, price_monthly/price_yearly nullable, currency default USD, max_students/max_staff nullable (null =
  unlimited), trial_days nullable, is_self_serve boolean, is_active boolean, sort_order.
- `schools` table additions (migration lives in Platform, same pattern as Sms adding `sms_cost_per_segment`):
  `plan_id` nullable FK (null = legacy/grandfathered, unrestricted — every NEW school always gets one),
  `trial_ends_at`, `subscription_expires_at` (paid-plan renewal date; also used as the hard expiry for
  Super-Admin-created offline/manual accounts), `is_demo` boolean default false, `provisioning_type` enum
  (self_service, offline_manual, super_admin) nullable, `stripe_customer_id`, `stripe_subscription_id`,
  `subscription_status` enum(trialing, active, past_due, canceled, expired) nullable.
- `pending_school_signups` (platform-level, no `school_id` — the school doesn't exist yet): school_name,
  desired_subdomain, plan_id, admin_name, admin_email, country_code, stripe_checkout_session_id, status
  enum(pending, completed, failed, expired), created_school_id nullable — same "own staging table, converted
  only on confirmation" pattern as `AdmissionApplication`, except the trigger here is a Stripe webhook (paid
  path) rather than an admin decision; Trial signups skip this table entirely and provision immediately (no
  payment round-trip to survive).
- `subscription_reminders` (HAS `school_id` — this one is genuinely school-scoped, just queried/created by a
  platform-level job rather than through `current_school_id`): school_id, milestone enum(day_7, day_1),
  sent_at — unique per (school_id, milestone) so the daily reminder job never double-sends.

### Decisions confirmed with the user
1. **Vendor payment gateway: Stripe globally** for self-serve Basic/Pro checkout — one processor regardless of
   school country, entirely separate from Payment module's per-school bKash/SSLCommerz/Stripe/PayPal gateways
   (those bill a school's own students; this bills the school itself). New `StripeCheckoutService`.
2. **Super Admin can ALSO manually create a school** for an offline-paid customer (bank transfer, cash,
   whatever) — no Stripe involved, sets `provisioning_type=offline_manual`, an explicit
   `subscription_expires_at`, and any plan. A daily job emails the school's admin a renewal reminder at 7 days
   and 1 day before `subscription_expires_at` (`subscription_reminders` makes this idempotent). Same
   reminder job also covers self-serve subscriptions nearing Stripe renewal, though Stripe itself handles the
   actual charge retry.
3. **Credential delivery: secure "set your password" link**, not a plaintext emailed password — standard
   Laravel signed-URL reset-style flow. Account is created with an unusable random password; the admin sets
   their own on first visit.
4. **Demo replaces the "request a demo" contact-sales form entirely** — no lead-capture table, no sales
   pipeline. One permanent shared school (`is_demo=true`) with fixed seed data and prefilled, publicly visible
   login credentials at a demo login page; a scheduled job wipes and reseeds it every 14 hours.
5. **Plan caps are enforced, not just stored** — `PlanLimitService` is called from the existing
   `StudentService::enrol()` and `StaffService::hire()` (shared-file edits, same as Payroll's abilities fix)
   and throws a 422 once `max_students`/`max_staff` is reached for schools that have a plan. Schools with
   `plan_id = null` (legacy/grandfathered) are never capped.
6. **Pricing/caps are placeholders** — informed by researching global ($2–15/student/year) and Bangladesh
   (BDT 30k–50k/year flat-license) market rates, deliberately undercutting BD competitors slightly. Stored
   entirely in the `plans` table so Super Admin can change them without a code deploy.

### Implementation notes (added once built, 2026-07-04)
- **`role:super_admin` middleware, not `ability:super_admin:*`** — a real problem surfaced while building the
  Super Admin portal: `User::abilitiesForRole()` gives BOTH `admin` and `super_admin` a bare `'*'` Sanctum
  ability, and Sanctum's `tokenCan()` special-cases bare `'*'` to satisfy ANY ability check. That means an
  ordinary school admin's token would ALSO pass an `ability:super_admin:*` gate — there was no way to
  distinguish them via abilities alone. Changing `admin`'s abilities away from `'*'` was rejected (the User
  module's `/v2/admin` route group is itself gated on literal `ability:*`, and every one of the 22 modules'
  existing tests already depends on admin's blanket access). Fix: added a `role` middleware alias
  (`Spatie\Permission\Middleware\RoleMiddleware`) and gate Platform's Super Admin routes on
  `role:super_admin` — a real Spatie role check, completely independent of Sanctum abilities. Zero changes to
  `User::abilitiesForRole()` or any other module. `SuperAdminSchoolTest::test_regular_admin_token_is_forbidden_from_super_admin_routes`
  is the regression test for this.
- **`schools.subdomain` added here, not earlier** — nothing before this module needed one; it didn't exist
  anywhere despite CLAUDE.md's Frontend Architecture section already assuming subdomain-per-school routing.
  Added as part of the same migration that adds plan/subscription fields.
- **Stripe integration is raw Http-facade calls**, not the `stripe-php` SDK — same pattern as
  `AnthropicAiChecker` (LMS) and `BkashGateway`/`SslcommerzGateway` (Payment): no new Composer dependency,
  `Http::fake(['api.stripe.com/*' => ...])` in tests. Webhook signature verification is plain
  `hash_hmac('sha256', ...)` against Stripe's documented `t=...,v1=...` header scheme — no SDK needed for that
  either.
- **`PendingSchoolSignup` → School provisioning is idempotent on webhook retry** — Stripe redelivers
  `checkout.session.completed` on any non-2xx response; a second delivery for an already-`completed` signup is
  a silent no-op, never a duplicate school (mirrors the DataImport/Sms/IdCard "swallow, don't rethrow"
  discipline, applied here to duplicate-delivery instead of exceptions).
- **Demo reset is intentionally narrow** — `DemoResetService` wipes/reseeds only Student and Staff rows for
  the one `is_demo` school (relying on each module's existing FK `cascadeOnDelete` to clean up dependents). A
  full synthetic dataset across all 22+ modules was judged out of proportion to this pass; the demo school's
  own academic structure (year/class/section) is assumed pre-seeded once, not rebuilt every reset — flagged as
  a follow-up (a `DemoSchoolSeeder` was NOT written).
- **Credential emails have no Blade views** — `SetPasswordMail`/`SubscriptionExpiringMail` use
  `Content::htmlString()` directly, same heredoc-HTML convention already established for PDFs in
  `PdfRenderingService`.

### Known gaps / follow-ups
- No `DemoSchoolSeeder` — the one `is_demo=true` school (with its academic structure + a fixed-password admin
  matching `config('platform.demo_password')`) must still be created once, manually or via a seeder not yet
  written. `DemoResetService`/`platform:demo-reset` only wipe+reseed its Student/Staff rows on schedule —
  they don't create the school itself if it's missing (logs a warning and no-ops).
- No admin UI/endpoint to browse `pending_school_signups` (e.g. a stuck/abandoned Stripe checkout) — only
  the webhook path reads them today.
- `SubscriptionReminderService` looks up "the school's admin" via `User::role('admin')->first()` — if a
  school has multiple admin users, only one (the first by default ordering) receives the reminder.
- Plan pricing/caps in `config/platform.php` are placeholders (see the pricing table above) — Super Admin can
  edit them via `PUT /v2/platform/admin/plans/{id}` without a deploy, but no currency-conversion or
  country-specific pricing exists yet (global USD via Stripe only, per the confirmed decision).

Everything above is now built — see the Build Order table's row 23 for test status once Docker confirms.

---

## Key Patterns

### Repository (cache-aside)
```php
class StudentRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache)
    {
        parent::__construct(Student::class, $cache);
    }

    public function activeByClass(int $classId, int $schoolId): Collection
    {
        return $this->remember(
            $this->cacheKey("school:{$schoolId}:class:{$classId}:active"),
            fn () => Student::where('school_id', $schoolId)
                ->where('class_id', $classId)
                ->where('status', 'active')
                ->get(),
        );
    }
}
```

### Observer (cache flush)
```php
class StudentObserver
{
    public function saved(Student $student): void
    {
        Cache::tags(['student'])->flush();
    }

    public function deleted(Student $student): void
    {
        Cache::tags(['student'])->flush();
    }
}
```

### Controller (thin)
```php
public function index(Request $request): StudentCollection
{
    $schoolId = app('current_school_id');
    return new StudentCollection(
        $this->service->all($schoolId)
    );
}
```

### Financial write (always transactional)
```php
DB::transaction(function () use ($data) {
    $payment = $this->repository->create($data);
    $this->ledgerRepository->recordDebit($payment);
    event(new PaymentRecorded($payment));
});
```

---

## Git Commit Convention

```
type(module): short description

Types: feat | fix | test | refactor | chore | docs
```

Aim for 2–3 commits per work session. 25 modules × ~10 steps = ~250 commits.

---

## After Every Module — Run & Ship (in this order)

When a module's code is complete, Claude must provide these commands, in this exact order
(replace `{module}` with the module name, e.g. `attendance`):

```bash
# 1. Run migrations
docker compose exec app php artisan migrate

# 2. Run the module's tests
docker compose exec app php artisan test tests/Feature/{Module}/ --no-coverage

# 3. Create the feature branch (do this BEFORE committing; ideally before coding starts)
git checkout dev
git pull origin dev
git checkout -b feature/{module}-module

# 4. Commit (one commit per 10-step stage where practical)
git add app/Modules/{Module}/ tests/Feature/{Module}/ <other touched files>
git commit -m "feat({module}): <short description>"

# 5. Merge back to dev
git checkout dev
git merge --no-ff feature/{module}-module
git push origin dev
git branch -d feature/{module}-module
```

Rules:
- Never merge with failing tests — fix and re-run step 2 first.
- Shared-file edits (AppServiceProvider, bootstrap/app.php, routes/console.php, CLAUDE.md status table) belong in the module's commits.
- Update the module's status in the Build Order table in the same branch before merging.
