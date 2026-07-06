# AGENTS.md — School Management System v2

Read automatically at the start of every session. Follow every rule here across all 26 modules.

## Project Overview
Multi-tenant SaaS school management platform.
Stack: Laravel 13 · PHP 8.3 · MySQL 8 · Redis 7 · Laravel Horizon · MinIO · Sanctum · Spatie Permission

## Frontend (decided, not yet built — starts only after all backend modules are done)
- Monorepo, 3 Next.js 15 apps: `apps/marketing` (vendor site, not tenant-scoped, features/pricing/contact/demo
  request — no backend endpoint yet for the contact form), `apps/school-site` (per-school public site, consumes
  Website module's `/public/*`), `apps/dashboard` (per-school logged-in app, consumes every other module's API).
- Tenant routing: subdomain per school (`{school}.yourapp.com` public site, `app.{school}.yourapp.com` or `/app`
  for dashboard). `schools.subdomain` column exists (Platform module).

## Architecture Rules
- Module path: `app/Modules/{ModuleName}/Http/{Controllers,Requests,Resources}`, `Models/`, `Repositories/`,
  `Services/`, `Observers/`, `database/migrations/`, `routes/api.php`.
- Controllers thin (max 40 lines/method) — business logic in Services.
- Every write endpoint: `FormRequest` with `authorize()`+`rules()`. Every response: `JsonResource` (never a
  raw Model).
- Repositories: `Cache::tags([...])->remember()` (see `StudentRepository`). Observers flush tags on
  saved/deleted. Platform-level models (no `school_id`, e.g. `Plan`) don't extend `BaseRepository`/
  `BaseService` — those assume `school_id` scoping.
- Financial/mark-entry writes: `DB::transaction()`, no cache.
- Sanctum: `middleware(['auth:sanctum', 'ability:admin:*'])`. Every table has `school_id` except platform-level
  (`schools`, `plans`, `pending_school_signups`). All queries scoped to `school_id` via `app('current_school_id')`
  (set by `ResolveSchool`; bypassed for `/api/v2/platform/*` and `/api/v2/health`).

## Naming Conventions
| Type | Convention | Example |
|------|-----------|---------|
| Module folder | PascalCase | `Student`, `Payment` |
| Service | `{Domain}Service.php` | `StudentService` |
| Repository | `{Domain}Repository.php` | `StudentRepository` |
| Resource | `{Model}Resource.php` | `StudentResource` |
| Store/Update request | `Store{Model}Request.php` / `Update{Model}Request.php` |
| Observer | `{Model}Observer.php` | `StudentObserver` |
| Migration | Laravel timestamp prefix | `2026_07_01_create_students_table.php` |

## Module Build Order
Build in dependency order.

| # | Module | Depends On | Status | Notes |
|---|--------|-----------|--------|-------|
| 1 | School | — | ✅ | School, SchoolPhone, SchoolOpeningHour; locale (currency/timezone/locale/academic_year_pattern), country_code, subdomain |
| 2 | Academic | School | ✅ | AcademicYear, SchoolClass, Section(+class_teacher_id), Subject, SubjectRelation, AcademicGroup, Version, Shift, ClassRoutine |
| 3 | User/Auth | — | ✅ | User+Sanctum+Spatie. Roles: `super_admin, admin, teacher, accountant, librarian, receptionist, student, parent` (real list — DevPlan's "moderator"/"Finance"/"Head Teacher" don't exist, never invent them) |
| 4 | Student | Academic, User | ✅ | Student, StudentAcademic, StudentSubject (optional/4th-subject enrollment) |
| 5 | Staff | Academic, User | ✅ | Staff (rfid_number) |
| 6 | Announcement | — | ✅ | Announcement |
| 7 | FeeItem | Academic | ✅ | FeeCategory, FeeItem, FeeDiscount |
| 8 | Payment | Student, FeeItem | ✅ | Invoice, Payment(multi-currency), Refund, StudentCredit, CreditTransaction, PaymentConfig, PaymentGatewayLog. Gateways by `country_code`: BD=bKash+SSLCommerz, else Stripe+PayPal; each declares `SUPPORTED_CURRENCIES` |
| 9 | Examination | Academic, Student | ✅ | ExamType, Exam, ExamSubject, ExamHall, ExamHallSeat, ExamSeating (anti_adjacency + blank_every) |
| 10 | Attendance | Student, Staff | ✅ tests green | see spec below |
| 11 | Mark | Examination, Attendance, Student | ✅ tests green | see spec below (needs `student_subjects`, done) |
| 12 | Leave | Student, Staff | ✅ tests green | LeaveType, StudentLeaveRequest, StaffLeaveRequest; approved leave overrides `absent`→`leave` via `WorkingDayService`; staff approval admin-only (no manager field) |
| 13 | Loan | Staff | ✅ tests green | StaffLoan, LoanSchedule; interest-free, request→approve, repayment/paid-marking deferred to Payroll |
| 14 | Certificate | Student, Mark | ✅ tests green | AdmitCard, TestimonialTemplate, Testimonial; Transfer Certificate lives in Student module; shared `App\Services\PdfRenderingService` (DomPDF, no Blade views) |
| 15 | IdCard | Student, Staff | ✅ tests green | IdCardTemplate, IdCardBatch, IdCardBatchFile; first queued job (Horizon `GenerateIdCardBatchJob`), 200-cards-per-PDF chunking, photos inlined as base64 (dompdf can't fetch remote URLs) |
| 16 | Report | Payment, Mark, Student | ✅ tests green | No new tables — pure aggregation. Fee Collection / Outstanding Dues / Student Ledger; JSON + streamed PDF; admin+accountant only; no cache |
| 17 | Sms | Student, Payment | ✅ tests green | SmsBatch, SmsLog; per-school billing (`schools.sms_api_key/sms_sender_id/sms_cost_per_segment`); `SmsSegmentCalculator` (GSM-7 160/153, unicode 70/67); stub `LogGateway` behind `SmsGatewayContract` |
| 18 | DataImport | Student, Academic | ✅ tests green | ImportBatch only (errors as JSON). Reuses `StudentService::enrol()`/`StaffService::hire()` per row; queued Horizon job reads MinIO file via `maatwebsite/excel` |
| 19 | OnlineAdmission | Academic, Student | ✅ tests green | AdmissionApplication (own table). Public `POST /v2/admission-applications` + status check (reference+phone). `approve()` calls `StudentService::enrol()` |
| 20 | Website | — | ✅ tests green | 9 tables: Page, PageRedirect, PageLayout, SiteLayout, SiteSetting, Menu, MenuItem, PageTemplate, WebsiteMedia. `layout_json` opaque LONGTEXT blob, every save is a NEW row (versioned). Public `/public/*` (pages, site-chrome, notices, staff, routine, stats, result-check) |
| 21 | Payroll *(optional)* | Staff | ✅ tests green | SalaryComponent, StaffSalaryValue, PayrollRun, PayrollEntry, SalaryCertificateRequest. Flat component sums only (no attendance proration). Integrates Loan's deferred repayment. Fixed a real bug: `User::abilitiesForRole()` never emitted `teacher:*`/`staff:*` wildcards, so those ability-gated routes never matched a real login |
| 22 | LMS *(optional)* | Academic, Student | ✅ tests green | Course, Lesson, Assignment, Submission, SubmissionAiCheck. Real Anthropic API integration (`AnthropicAiChecker`, Http-facade, no SDK). Introduced `school_module_settings`/`CheckModuleEnabled` (`module.enabled:{name}` middleware) — also retrofitted onto Payroll |
| 23 | Platform | — | ✅ tests green | Plan, PendingSchoolSignup, SubscriptionReminder. Platform-level (not tenant-scoped) — see spec below |
| 24 | Library *(optional)* | Student, Staff | ✅ tests green | Book, LibraryMember, BorrowRecord, borrow/return workflow |
| 25 | Transport *(optional)* | Student, Payment | ⬜ pending |
| 26 | Messaging *(optional)* | User | ⬜ pending |

## The 10-Step Pattern (one commit per step)
1. Migration(s) 2. Model 3. Repository (cache-aside) 4. Service 5. Observer (cache flush)
6. FormRequests (Store+Update) 7. Resource+Collection 8. Controller+routes 9. Tests 10. Pint + docblocks

## DO NOT
- Use `DB::table()` in controllers/services — always Repository.
- Return Eloquent models directly — always a Resource.
- Put business logic in controllers.
- Use Laravel Passport — Sanctum only.
- Cache financial or mark-entry writes.
- Skip `school_id` scoping.
- Use `127.0.0.1` for DB/Redis in Docker — use service names (`db`, `redis`).

## Global Product Rules
V2 is global (v1 was BD-only) — never bake BD assumptions into core code.
- Locale (currency/timezone/locale/academic_year_pattern, weekend days) — BD values are a seed template only.
- Gateway policy by `country_code`: BD=bKash+SSLCommerz, else Stripe+PayPal. Each school has its own gateway
  credentials. Gateways declare `SUPPORTED_CURRENCIES`; never hardcode a gateway choice.
- Grading templates: `bd_national_5.0`, `us_letter_4.0`, `uk_9_1`, `percentage_only` — school picks one, seeds
  `grade_boundaries`, editable per class after.
- Result strategy is pluggable per class: `bd_national`, `simple_average`, `weighted_average`, `percentage_only`.
- All user-facing strings via translation keys (English default).
- Institution code is generic (label configurable). Addresses are free-form, no BD geo tables.
- Scope: BD schools class 3–10 + college (HSC 11–12). Degree/credit-hour systems out of scope.

## Attendance Module Spec (Module 10)
- Student attendance: once-daily enum `present|absent|late|half_day|leave`, bulk upsert per class/section,
  unique (school_id, student_id, date).
- Staff attendance: punch-based (check_in/check_out, `source` enum(manual|rfid), `is_auto_closed`).
- Auto clock-out: scheduled job after each school's closing time (school timezone) — check_out = closing time
  from `school_opening_hours`, NEVER job-run time. Policy: close_at_closing_time (default) | max_shift_hours | off.
- Tables: `student_attendances`, `staff_attendances`, `attendance_settings` (auto_close_policy,
  max_shift_hours, edit_window_days, late_threshold_minutes).
- Attendance % denominator = working days in enrollment period; retroactive closure = "void day" excludes a
  date from all % calcs.
- Corrections: editable within `edit_window_days` (default 7) by recording teacher; older = admin only, audited.
- Leave integration: approved leave auto-sets `leave`, overriding `absent` only.
- Mark integration: attendance-division marks SNAPSHOTTED at entry time — later edits never change results.
- RFID: first punch = check_in, last = check_out. Dates always school-local. No cache on writes.

## Mark Module Spec (Module 11)
- Grade defaults (bd_national): A+ 80–100(5.00), A 70–79(4.00), A− 60–69(3.50), B 50–59(3.00), C 40–49(2.00),
  D 33–39(1.00), F 0–32(0.00).
- Divisions per exam subject (not per class): `mark_divisions` (exam_id, exam_subject_id, name, max_marks,
  pass_mark, display_order).
- Tables: `mark_divisions`, `mark_settings` (class_id, mode enum(mark|grade), result_strategy),
  `grade_boundaries`, `marks` (marks_obtained, is_absent, entered_by, locked_at), `exam_results` (total_marks,
  percentage, grade, gpa, is_pass, merit_position, is_locked).
- `exam_results` written on calculation, locked after Moderator approval — never recompute a locked result.
  Tabulation cached under `Cache::tags(['tabulation'])`.
- Must support: absent≠zero ("Ab"), optional/4th-subject GPA bonus (bd_national: GPA=(Σcompulsory GP +
  max(0,optional GP−2.00))/compulsory count, cap 5.00), combined subjects (shared pass mark), merit tie-break
  (GPA→total→percentage, failed ranked after passed), N/A for non-enrolled.
- Division/exam-weight templates are seed data (`config/grading.php`), not code.
- Grace marks: separate audited `grace_marks` column (never mixed into `marks_obtained`), per-school cap.
- No cache on mark writes.

## Platform Module Spec (Module 23)
Added outside the original 25-module list — powers the marketing site's "buy a package → pay → get logged in"
flow (a Super Admin Portal the DevPlan had but AGENTS.md silently dropped). **Platform-level, not
tenant-scoped** — Super Admin sees every school; public signup/checkout run before a school exists.

Plans (seed data in `config/platform.php`, editable by Super Admin, placeholders not final pricing):
| Plan | Price | Caps | Notes |
|---|---|---|---|
| Demo | Free, not purchasable | 20 students/10 staff | ONE shared `is_demo=true` school; prefilled public login page; resets every 14h |
| Trial | Free, 30 days | 100/15 | Self-serve, no payment |
| Basic | $19/mo, $190/yr | 500/40 | Stripe Checkout |
| Pro | $49/mo, $490/yr | Unlimited | Stripe Checkout |

Schema: `plans` (platform-level), `schools` gets `subdomain`, `plan_id` (nullable=legacy/uncapped),
`trial_ends_at`, `subscription_expires_at`, `is_demo`, `provisioning_type` enum(self_service/offline_manual/
super_admin), `stripe_customer_id`, `stripe_subscription_id`, `subscription_status`. `pending_school_signups`
(platform-level staging row for the Stripe round-trip, same pattern as `AdmissionApplication` but
webhook-triggered). `subscription_reminders` (school-scoped, milestone enum(day_7/day_1), unique per school+
milestone — idempotent).

Key decisions: Stripe globally for vendor billing (separate from Payment module's per-school student
gateways); Super Admin can also create offline-paid schools with explicit expiry + reminders; credentials
delivered via signed "set your password" link, never plaintext; Demo replaces any "request a demo" form
entirely; plan caps ENFORCED via `PlanLimitService` hook in `StudentService::enrol()`/`StaffService::hire()`
(schools with `plan_id=null` never capped); `role:super_admin` middleware (real Spatie role check) gates the
Super Admin portal — NOT `ability:super_admin:*`, since `admin`/`super_admin` both carry a bare Sanctum `'*'`
which would satisfy any ability check. Stripe integration is raw Http-facade calls (no SDK), webhook signature
via `hash_hmac('sha256', ...)`. Provisioning idempotent on webhook retry.

Known gaps: no `DemoSchoolSeeder` (demo school's academic structure + fixed-password admin must be created
once, manually or via a seeder not yet written); demo reset (`platform:demo-reset`, runs ~every 14h via
00:00/14:00 cron) only wipes/reseeds Student/Staff; subscription reminders email only the first admin found
per school; pricing/caps are placeholders (no currency conversion).

## Key Patterns

**Repository (cache-aside):**
```php
class StudentRepository extends BaseRepository
{
    public function __construct(CacheRepository $cache) { parent::__construct(Student::class, $cache); }
    public function activeByClass(int $classId, int $schoolId): Collection
    {
        return $this->remember($this->cacheKey("school:{$schoolId}:class:{$classId}:active"),
            fn () => Student::where('school_id', $schoolId)->where('class_id', $classId)
                ->where('status', 'active')->get());
    }
}
```

**Observer (cache flush):**
```php
class StudentObserver
{
    public function saved(Student $student): void { Cache::tags(['student'])->flush(); }
    public function deleted(Student $student): void { Cache::tags(['student'])->flush(); }
}
```

**Financial write (always transactional):**
```php
DB::transaction(function () use ($data) {
    $payment = $this->repository->create($data);
    $this->ledgerRepository->recordDebit($payment);
    event(new PaymentRecorded($payment));
});
```

## Gotchas Learned (apply these before writing new code)
- **Sanctum `tokenCan('*')` matches ANY ability string requested** — a token with bare `'*'` satisfies every
  `ability:`/`tokenCan()` check. Two same-privileged roles (e.g. `admin` vs `super_admin`) can't be
  distinguished via abilities alone — use a real `role:` (Spatie) middleware for that instead.
- **Never type-hint a second `Illuminate\Http\Request $x` alongside a `FormRequest` subclass** in one
  controller method — `FormRequest` extends `Request`, so Laravel's dependency resolver treats it as already
  satisfied and silently skips injecting the second param, causing an `ArgumentCountError`. Just use the
  FormRequest instance itself (it inherits every `Request` method).
- **Mailables implementing `ShouldQueue`**: `Mail::send()` silently redirects through `queue()`. Under
  `Mail::fake()` that's tracked as "queued," not "sent" — use `Mail::assertQueued()`, not `assertSent()`.
- **Fresh-model `JsonResource` auto-returns 201** (via `wasRecentlyCreated`). Calling `->fresh()` on a
  just-created model discards that flag (200 instead) — use `->load()` if you need relations but want to keep
  201. Force `->setStatusCode(200)` explicitly on idempotent PUT/toggle endpoints that use a freshly-inserted row.
  Force `->setStatusCode(200)` explicitly on idempotent PUT/toggle endpoints that use a freshly-inserted row.
- **Sync queue does NOT swallow job exceptions** — under `QUEUE_CONNECTION=sync`, an uncaught exception in a
  job's `handle()` propagates straight into the dispatching HTTP request as a 500. Every queued job must
  catch everything internally and never rethrow.
- **Sanctum guard caching in tests**: call `$this->app['auth']->forgetGuards()` every time a test switches to
  a different user's token, or Laravel resolves the previous (cached) user.
- **Explicit `protected $table`** needed whenever a migration's table name doesn't match Eloquent's
  pluralized-class-name guess (e.g. prefixed names like `lms_courses`).
- **`ForbiddenHttpException` doesn't exist** in Symfony — the 403 exception is `AccessDeniedHttpException`.
- Date-range queries: use `whereDate`, not `whereBetween`, against a `date`-cast column; use the full
  `Y-m-d H:i:s` string in `assertDatabaseHas` (SQLite stores `date` casts as datetime).

## Git Commit Convention
```
type(module): short description
Types: feat | fix | test | refactor | chore | docs
```
Aim for 2–3 commits per session, one per 10-step stage where practical.

## After Every Module — Run & Ship
```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test tests/Feature/{Module}/ --no-coverage

git checkout dev && git pull origin dev
git checkout -b feature/{module}-module
git add app/Modules/{Module}/ tests/Feature/{Module}/ <other touched shared files>
git commit -m "feat({module}): <short description>"
git checkout dev
git merge --no-ff feature/{module}-module
git push origin dev
git branch -d feature/{module}-module
```
Rules: never merge with failing tests; shared-file edits (AppServiceProvider, bootstrap/app.php,
routes/console.php, this file's Build Order table) belong in the module's commits; update the module's status
in the Build Order table before merging.
