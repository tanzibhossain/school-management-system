# CLAUDE.md — School Management System v2

Claude Code reads this file automatically at the start of every session.
Follow every rule here without exception across all 25 modules.

---

## Project Overview

Multi-tenant SaaS school management platform.
Stack: Laravel 13 · PHP 8.3 · MySQL 8 · Redis 7 · Laravel Horizon · MinIO · Sanctum · Spatie Permission

**Preferred model:** `claude-fable-5` — switch with `/model fable` at the start of every session (requires Claude Code v2.1.170+, run `claude update` first).

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
| 10 | Attendance | Student, Staff | ⬜ next — daily student + staff attendance (manual + RFID-ready); feeds Mark's attendance division and attendance SMS |
| 11 | Mark | Examination, Attendance, Student | ⬜ pending — see "Mark Module — Agreed Spec" below |
| 12 | Leave | Student, Staff | ⬜ pending |
| 13 | Loan | Staff | ⬜ pending |
| 14 | Certificate | Student, Mark | ⬜ pending |
| 15 | IdCard | Student, Staff | ⬜ pending |
| 16 | Report | Payment, Mark, Student | ⬜ pending |
| 17 | Sms | Student, Payment | ⬜ pending — SMS cost logic must be encoding-aware (unicode/Bangla = 70 chars/segment, GSM-7 = 160) |
| 18 | DataImport | Student, Academic | ⬜ pending |
| 19 | OnlineAdmission | Academic, Student | ⬜ pending |
| 20 | Website | — | ⬜ pending |
| 21 | Payroll *(optional)* | Staff | ⬜ pending |
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

## Mark Module — Agreed Spec (Module 11)

Decisions reconciled from v1 code + DevPlan + review (2026-07-02). Where the DevPlan docx conflicts with this section, THIS section wins.

- **Grade defaults (bd_national template)**: A+ 80–100 (5.00), A 70–79 (4.00), A− 60–69 (3.50), B 50–59 (3.00), C 40–49 (2.00), D 33–39 (1.00), F 0–32 (0.00). The DevPlan's 4.0-scale defaults are WRONG.
- **Divisions per exam subject** (not per class): `mark_divisions` — school_id, exam_id, exam_subject_id, name, max_marks, pass_mark (nullable), display_order. Subject-level pass mark already exists on `exam_subjects.pass_marks`.
- **Tables**: `mark_divisions`, `mark_settings` (school_id, class_id, mode enum(mark|grade), result_strategy), `grade_boundaries` (school_id, class_id, grade_label, min_percent, max_percent, gpa_point), `marks` (school_id, exam_id, student_id, mark_division_id, marks_obtained, is_absent, entered_by, locked_at), `exam_results` (school_id, exam_id, student_id, total_marks, percentage, grade, gpa, is_pass, merit_position, is_locked).
- **Persist results**: `exam_results` rows are written on calculation and locked after Moderator approval — no recompute-on-read for locked results. Tabulation view cached via `Cache::tags(['tabulation'])`, flushed by MarkObserver.
- **Must support** (all existed in v1): absent handling (`is_absent`, display "Ab", absent ≠ zero), optional/4th subject with GPA bonus (bd_national: GPA = (Σ compulsory GP + max(0, optional GP − 2.00)) / compulsory count, cap 5.00), combined subjects (e.g. Bangla 1st + 2nd paper graded as one with combined pass mark), merit position with tie handling (GPA → total → percentage; failed ranked after passed), N/A for non-enrolled subjects.
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

Aim for 2–3 commits per work session. 24 modules × ~10 steps = ~240 commits.
