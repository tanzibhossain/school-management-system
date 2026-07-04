# School Management System v2 — Overview & Architecture Rules

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

See also (in this same `.clinerules/` folder): `01-build-status.md` (module status table), `02-module-specs.md`
(Attendance/Mark/Platform detailed specs), `03-gotchas.md` (hard-won lessons + code patterns), `04-workflow.md`
(global product rules, git convention, run/ship commands).
