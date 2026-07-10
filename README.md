# School Management System v2

A multi-tenant SaaS school management platform built with Laravel 13, with a server-rendered Laravel Blade + Bootstrap 5 admin UI. Each school gets its own subdomain or custom domain. Everything runs self-hosted in Docker on a single Ubuntu VPS.

---

## Tech Stack

- **Backend:** Laravel 13 · PHP 8.3 · MySQL 8 · Redis 7 · Laravel Horizon
- **Admin UI:** Laravel Blade · Bootstrap 5.3 · DataTables 2 · session auth (in this repo; see `docs/modules/27-blade-admin-plan.md`)
- **File Storage:** MinIO (self-hosted, S3-compatible)
- **Auth:** Laravel Sanctum · Spatie Laravel Permission
- **Email:** Resend (platform-level)
- **PDF:** barryvdh/laravel-dompdf
- **API Docs:** Scribe + Postman export

---

## Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installed and running
- Git
- [Composer](https://getcomposer.org/download/) (for the initial project creation only)
- A code editor (VS Code recommended)
- (No Node toolchain needed — the Blade admin loads Bootstrap/DataTables/jQuery from CDN)

After the initial setup, all `composer` and `php artisan` commands run **inside the Docker container** — no local PHP needed day-to-day.

---

## Local Development Setup

### 1. Clone the repository

```powershell
git clone <repo-url>
cd school-management-backend
```

### 2. Environment

```powershell
copy .env.example .env
```

The `.env.example` already has the correct values for local Docker development. Only update `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, and `RESEND_API_KEY` when you need real file uploads or email sending.

### 3. Start Docker and run setup

```powershell
# Build and start all containers (3-5 min the first time)
docker compose up -d --build

# Verify all containers are running (db, redis, minio, app, nginx)
docker compose ps

# Generate app key, run migrations, link storage
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan storage:link
```

### 4. Verify everything is working

Visit `http://localhost:8080/api/v2/health` — you should see a JSON response confirming Laravel, DB, and Redis are all connected.

---

## Daily Workflow

```powershell
docker compose up -d                                     # start work
docker compose down                                      # stop work
docker compose ps                                        # check running containers
docker compose logs -f app                               # watch Laravel logs live
docker compose exec app php artisan migrate              # run migrations
docker compose exec app php artisan test                 # run tests
docker compose exec app composer require vendor/package  # install package
docker compose exec app bash                             # open shell in container
```

> **Port note:** This project uses port **8080** (not 8000). Windows Hyper-V reserves 7980–8079 which includes 8000.

---

## Services

| Service | URL | Notes |
|---------|-----|-------|
| Laravel API | http://localhost:8080 | Nginx → PHP-FPM |
| Health Check | http://localhost:8080/api/v2/health | First thing to verify |
| Admin UI (Blade) | http://localhost:8080/login | Served by Laravel; log in with a school admin account |
| MinIO Console | http://localhost:9001 | File storage browser UI |
| Horizon Dashboard | http://localhost:8080/horizon | Queue monitoring |
| MySQL | localhost:3307 | Connect via TablePlus or DBeaver |

---

## Roles

| Role | Access |
|------|--------|
| Super Admin | Platform-wide — manages schools and plans |
| Head Teacher | Full school admin |
| Moderator | Page builder, admissions, marksheet, announcements, class promotion |
| Teacher | Own classes: enter marks and attendance |
| Finance | Payments, waivers, payroll structures, salary certificates |
| Librarian | Library module only |
| Student | Own records: results, attendance, fees, timetable |
| Parent | Child's records |

---

## Git Workflow — Module & Feature Development

Every module follows the same branch → build → commit → merge cycle. Each step of a module is a separate commit. This keeps your GitHub contribution graph active every day and gives you a clean, traceable history.

### Branch strategy

```
main          — production only, tagged releases (v1.0, v1.1)
develop       — integration branch, all features merge here first
feature/*     — one branch per module or feature, deleted after merge
```

### Starting a new module

```bash
# Always branch off develop
git checkout develop
git pull origin develop
git checkout -b feature/student-module
```

### Committing as you build (one commit per step)

The 10-step pattern per module maps to ~10 commits:

```bash
# Step 1 — migration
git add -A && git commit -m "feat(student): create students and contacts migrations"

# Step 2 — model
git add -A && git commit -m "feat(student): add Student model with relationships and scopes"

# Step 3 — repository
git add -A && git commit -m "feat(student): add StudentRepository with Redis cache-aside"

# Step 4 — service
git add -A && git commit -m "feat(student): add StudentService with enrolment logic"

# Step 5 — observer
git add -A && git commit -m "feat(student): add StudentObserver for cache invalidation"

# Step 6 — FormRequests
git add -A && git commit -m "feat(student): add StoreStudentRequest and UpdateStudentRequest"

# Step 7 — resource
git add -A && git commit -m "feat(student): add StudentResource and StudentCollection"

# Step 8 — controller + routes
git add -A && git commit -m "feat(student): add StudentController and api routes"

# Step 9 — tests
git add -A && git commit -m "test(student): add feature and unit tests for student module"

# Step 10 — cleanup and final check
git add -A && git commit -m "refactor(student): pint fixes and docblock cleanup"

# Push the branch
git push origin feature/student-module
```

### Merging when the module is complete

```bash
git checkout develop
git merge --no-ff feature/student-module -m "merge: student module complete"
git push origin develop

# Delete the feature branch
git branch -d feature/student-module
git push origin --delete feature/student-module
```

### Tagging a release

```bash
git checkout main
git merge --no-ff develop -m "release: v1.0 — core modules complete"
git tag -a v1.0 -m "v1.0 — School, Academic, Auth, Student, Staff"
git push origin main --tags
```

### Commit format

```
type(module): short description
```

| Type | When to use |
|------|-------------|
| `feat` | New functionality |
| `fix` | Bug fix |
| `test` | Adding or updating tests |
| `refactor` | Code cleanup, no behaviour change |
| `chore` | Config, dependencies, Docker changes |
| `docs` | README, CLAUDE.md, comments |

**Examples:**
```bash
git commit -m "feat(payment): add bKash payment gateway integration"
git commit -m "fix(attendance): correct ZKTeco duplicate entry handling"
git commit -m "test(mark): add unit test for grade boundary lookup"
git commit -m "chore(docker): add MinIO health check to docker-compose"
```

### Daily commit goal

Aim for **2–3 commits every work session**. 41 modules × ~10 commits = ~410 commits across 41 weeks. That fills your GitHub contribution graph with consistent green squares — visible proof of daily progress.

```bash
# Rule: never end a work session without committing what you built
git add -A
git commit -m "feat(library): add BookRepository with issue and return logic"
git push origin feature/library-module
```

---

## API Documentation

```powershell
docker compose exec app php artisan scribe:generate
```

Docs at `http://localhost:8080/docs` · Postman collection exported automatically.

---

## Common Issues

| Problem | Fix |
|---------|-----|
| Port 8000 fails on Windows | Use port 8080 — Hyper-V reserves 7980–8079 |
| `DB_HOST` connection refused | Use `db` not `127.0.0.1` in `.env` |
| `REDIS_HOST` connection refused | Use `redis` not `127.0.0.1` in `.env` |
| Composer/artisan commands fail | Run inside container: `docker compose exec app composer ...` |
| MinIO uploads fail | Check `AWS_ENDPOINT=http://minio:9000` and `AWS_USE_PATH_STYLE_ENDPOINT=true` |
