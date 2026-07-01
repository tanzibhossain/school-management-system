# CLAUDE.md — School Management System v2

Claude Code reads this file automatically at the start of every session.
Follow every rule here without exception across all 24 modules.

---

## Project Overview

Multi-tenant SaaS school management platform.
Stack: Laravel 13 · PHP 8.3 · MySQL 8 · Redis 7 · Laravel Horizon · MinIO · Sanctum · Spatie Permission

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
| 10 | Mark | Examination, Student | ⬜ next |
| 11 | Leave | Student, Staff | ⬜ pending |
| 12 | Loan | Staff | ⬜ pending |
| 13 | Certificate | Student, Mark | ⬜ pending |
| 14 | IdCard | Student, Staff | ⬜ pending |
| 15 | Report | Payment, Mark, Student | ⬜ pending |
| 16 | Sms | Student, Payment | ⬜ pending |
| 17 | DataImport | Student, Academic | ⬜ pending |
| 18 | OnlineAdmission | Academic, Student | ⬜ pending |
| 19 | Website | — | ⬜ pending |
| 20 | Payroll *(optional)* | Staff | ⬜ pending |
| 21 | LMS *(optional)* | Academic, Student | ⬜ pending |
| 22 | Library *(optional)* | Student, Staff | ⬜ pending |
| 23 | Transport *(optional)* | Student, Payment | ⬜ pending |
| 24 | Messaging *(optional)* | User | ⬜ pending |

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
