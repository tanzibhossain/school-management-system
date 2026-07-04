# CLAUDE.md — School Management System v2

Claude Code reads this file automatically at the start of every session.
Follow every rule here without exception across all 25 modules.

---

## Project Overview

Multi-tenant SaaS school management platform.
Stack: Laravel 13 · PHP 8.3 · MySQL 8 · Redis 7 · Laravel Horizon · MinIO · Sanctum · Spatie Permission

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
| 10 | Attendance | Student, Staff | 🔶 code complete 2026-07-02 — `app/Modules/Attendance` (StudentAttendance, StaffAttendance, AttendanceSetting, Holiday); awaiting test run in Docker |
| 11 | Mark | Examination, Attendance, Student | 🔶 code complete 2026-07-02 — `app/Modules/Mark` + `student_subjects` prerequisite; 4 result strategies, templates in `config/grading.php`; awaiting test run in Docker |
| 12 | Leave | Student, Staff | ✅ done 2026-07-03 — `app/Modules/Leave` (LeaveType, StudentLeaveRequest, StaffLeaveRequest); approved student leave overrides `absent`→`leave` via WorkingDayService; staff approval admin-only (no manager field yet); tests green |
| 13 | Loan | Staff | ✅ done 2026-07-03 — `app/Modules/Loan` (StaffLoan, LoanSchedule); interest-free advances, request→approve workflow like Leave, admin+accountant decide; repayment/installment marking deferred until Payroll (#21) exists; tests green |
| 14 | Certificate | Student, Mark | ✅ done 2026-07-03 — `app/Modules/Certificate` (AdmitCard, TestimonialTemplate, Testimonial); Transfer Certificate stays in the Student module (not duplicated) but was retrofitted to actually generate PDFs; shared `App\Services\PdfRenderingService` wraps DomPDF; tests green |
| 15 | IdCard | Student, Staff | 🔶 code complete 2026-07-03 — `app/Modules/IdCard` (IdCardTemplate, IdCardBatch, IdCardBatchFile); first queued-job module (Horizon `GenerateIdCardBatchJob`, sync in tests), 200-cards-per-PDF chunking, base64-inlined photos/logos for dompdf; awaiting test run in Docker |
| 16 | Report | Payment, Mark, Student | 🔶 code complete 2026-07-04 — `app/Modules/Report` (no new tables — pure aggregation over Payment's schema); Fee Collection, Outstanding Dues, Student Ledger reports; JSON + streamed PDF (`?format=pdf`, no MinIO storage); admin+accountant only; awaiting test run in Docker |
| 17 | Sms | Student, Payment | 🔶 code complete 2026-07-04 — `app/Modules/Sms` (SmsBatch, SmsLog); per-school billing (School.sms_api_key/sms_sender_id/sms_cost_per_segment); GSM-7/unicode-aware `SmsSegmentCalculator` (160/153 septets, 70/67 unicode); stub `LogGateway` behind `SmsGatewayContract` (no real provider wired); queued `SendSmsBatchJob` (Horizon, same pattern as IdCard); manual bulk SMS + due reminders + resend; awaiting test run in Docker |
| 18 | DataImport | Student, Academic | 🔶 code complete 2026-07-04 — `app/Modules/DataImport` (ImportBatch only, no per-row child table — errors stored as JSON on the batch); student **and** staff/teacher import in scope (Staff module reused even though not in this row's dependency list); one-pass validate-and-insert with a report (success/skipped counts + per-row `{row, messages}` errors), not a staging-table review UI; each row calls the *existing* `StudentService::enrol()` / `StaffService::hire()` directly rather than duplicating create logic; class/section/academic-year/designation/department resolved from text by name, scoped to `school_id`; uploaded sheet stored in MinIO before the queued job (Horizon `ImportBatchJob`, same swallow-don't-rethrow pattern as IdCard/Sms) reads it back via `maatwebsite/excel` (already a dependency); downloadable sample templates at `GET /v2/data-imports/template?type=`; admin-only; awaiting test run in Docker |
| 19 | OnlineAdmission | Academic, Student | 🔶 code complete 2026-07-04 — `app/Modules/OnlineAdmission` (AdmissionApplication — its own table, never a half-formed Student row); public unauthenticated `POST /v2/admission-applications` (throttled) + public `GET .../status?reference=&guardian_phone=` (reference number alone is guessable, so phone must also match); no `section_id` captured at application (placement decided at approval, when capacity is known); `approve()` takes `admission_number` (never auto-generated anywhere in this codebase) + `section_id` and calls the *existing* `StudentService::enrol()` in the same action — same reuse pattern as DataImport; admin-only for review/approve/reject (the DevPlan's "moderator" role/ability was never actually built — real roles are `super_admin, admin, teacher, accountant, librarian, receptionist, student, parent` per `RoleSeeder`, and every other module gates on `admin:*`); status notifications (SMS/email) explicitly deferred; awaiting test run in Docker |
| 20 | Website | — | 🔶 code complete 2026-07-04 — `app/Modules/Website` (Page, PageRedirect, PageLayout, SiteLayout, SiteSetting, Menu, MenuItem, PageTemplate, WebsiteMedia — full DevPlan Sprint-1 backend scope, 9 tables); layout stored as an opaque `layout_json` LONGTEXT blob on both `page_layouts` and `site_layouts` (Next.js/Craft.js owns block structure, Laravel never parses it); every layout save is a NEW row (`const UPDATED_AT = null;`, never an update) — versioned revisions, `PageService::restore()` copies an old row's json into a new row rather than rewinding; slug change on `PageService::update()` auto-creates a `page_redirects` row inside `DB::transaction()`; `MenuService::replaceItems()` is delete-all-then-recreate for the whole tree (one level of nesting only — grandchildren rejected in `ReplaceMenuItemsRequest::withValidator()`); `SiteSetting` is a one-row-per-school singleton via `forSchool()`/`firstOrCreate` (mirrors `AttendanceSetting`); admin:* only for all `/v2/website/*` write endpoints; public unauthenticated `/public/*` routes (`throttle:60,1`) serve dynamic blocks — Notice Board reuses `AnnouncementRepository::listVisible()` as-is, Staff/Teacher List filters by `designation_id`/`department_id` (documented gap: no subject-relation filter, Staff has none), Class Routine joins `class_routines`, Stats Counter counts active students/staff, and Result Checker is entirely NEW code inside `PublicPortalService::checkResult()` (roll-number + exam lookup, only returns `is_locked=true` results, published exams only — Mark's own `ExamResultController` was NOT touched); tests in `tests/Feature/Website/` (Page, Menu, SiteSetting, SiteLayout, PageTemplate, WebsiteMedia, PublicPortal); awaiting test run in Docker |
| 21 | Payroll *(optional)* | Staff | 🔶 code complete 2026-07-04 — `app/Modules/Payroll` (SalaryComponent, StaffSalaryValue, PayrollRun, PayrollEntry, SalaryCertificateRequest); gated `admin:*,accountant:*` (the DevPlan's "Finance"/"Head Teacher" roles don't exist in `RoleSeeder`, same resolution as every other module); default earning/deduction components (`config/payroll.php`, seed data not logic) lazily seeded per school on first access, editable/trashable (never hard-deleted, so historic breakdowns stay meaningful); calculation is flat component sums only (`gross = Σearnings`, `net = gross − Σdeductions`) — no attendance proration, matching the DevPlan's `calculateGrossAndNet` exactly; `PayrollEntry.breakdown` JSON snapshots every earning/deduction/loan line at process time (mirrors Mark's snapshot-marks-at-entry-time rule) so later component/loan edits never silently alter an already-processed run; `PayrollService::processRun()` is idempotent (reprocessing a still-draft run wipes and regenerates entries, like Attendance's daily register) and pulls due, unpaid `LoanSchedule` installments in as an extra deduction line, marking them `is_paid`/`paid_amount`/`paid_at` on `approveRun()` — fulfilling what Loan's own docblocks said they were waiting for; no endpoint transitions a run to `paid` (the DevPlan's own ROUTES list never defines one — documented gap); payslips/salary certificates render via the shared `PdfRenderingService` (same heredoc-HTML-string pattern as Certificate, no Blade views); self-service (`GET /v2/payroll/staff/me/payslips`, `/me/certificates`, `POST /salary-certificate`) required fixing a pre-existing bug in `User::abilitiesForRole()` — Sanctum's `tokenCan()` is a literal string match, so `ability:staff:*`/`teacher:*` route gates never actually matched a real login's narrow-scoped abilities (only hand-crafted test tokens passed); fixed by adding matching wildcards (`teacher:*`, `staff:*`, `accountant:*`, etc.) per role, which also retroactively un-breaks the same latent gap in Leave and Loan's staff self-service routes; school_module_settings/`CheckModuleEnabled` toggle (DevPlan's generic optional-module gate) deliberately deferred — better designed once, for all of Payroll/LMS/Library/Transport/Messaging together; tests in `tests/Feature/Payroll/` (SalaryComponent, StaffSalary, PayrollRun incl. loan-deduction integration, Payslip, SalaryCertificate); awaiting test run in Docker |
| 22 | LMS *(optional)* | Academic, Student | ⬜ pending |
| 23 | Library *(optional)* | Student, Staff | ⬜ pending |
| 24 | Transport *(optional)* | Student, Payment | ⬜ pending |
| 25 | Messaging *(optional)* | User | ⬜ pending |

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
