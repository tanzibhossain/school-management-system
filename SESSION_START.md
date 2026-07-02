# Session Start — School Management System v2

Paste this file content at the start of every new Cowork or Claude Code session.
Switch model to Fable 5 first: click the model picker → select **Claude Fable 5**.

---

## Project Location

Backend:  `D:\dev\school-management-backend`  (Laravel 13 · PHP 8.3)
Frontend: `D:\dev\school-management-main`     (Next.js 15 — not started yet)

Run everything in Docker:
```bash
docker compose exec app php artisan <command>
```

---

## What Is Already Built (Modules 1–9)

| # | Module | Folder | Key Models |
|---|--------|--------|-----------|
| 1 | School | `app/Modules/School` | School, SchoolPhone, SchoolOpeningHour |
| 2 | Academic | `app/Modules/Academic` | AcademicYear, SchoolClass, Section, Subject, SubjectRelation, AcademicGroup, Version, Shift |
| 3 | User / Auth | `app/Modules/User` | User + Sanctum + Spatie roles |
| 4 | Student | `app/Modules/Student` | Student, StudentAcademic |
| 5 | Staff | `app/Modules/Staff` | Staff |
| 6 | Announcement | `app/Modules/Announcement` | Announcement |
| 7 | FeeItem | `app/Modules/FeeItem` | FeeCategory, FeeItem, FeeDiscount |
| 8 | Payment | `app/Modules/Payment` | Invoice, InvoiceItem, Payment, Refund, StudentCredit, CreditTransaction, PaymentConfig, PaymentGatewayLog |
| 9 | Examination | `app/Modules/Examination` | ExamType, Exam, ExamSubject, ExamHall, ExamHallSeat, ExamSeating |

### Key Examination Details (module 9)
- `ExamSeating` table is named `exam_seating` (not `exam_seatings`) — model has `protected $table = 'exam_seating'`
- Seating strategies: `sequential`, `interleave_group`, `interleave_section`, `anti_adjacency`
- `anti_adjacency` uses 2D row-offset algorithm — no same-group students front/back/left/right
- `blank_every` param on assign request leaves empty buffer seats
- Hall layout stored as JSON `layout_config` with `rows`, `sides[]`, `blocked_rows[]`
- Routes auto-loaded via glob in `routes/api.php`

---

## What Is Next — Module 10: Attendance, then Module 11: Mark

**Full agreed specs live in CLAUDE.md** (sections "Global Product Rules" and "Mark Module — Agreed Spec"). Where the DevPlan docx conflicts with CLAUDE.md, CLAUDE.md wins.

### Module 10 — Attendance (build first)
- **Depends on:** Student (#4), Staff (#5) — both complete.
- Daily student attendance (per class/section) + staff attendance (manual entry now, RFID-ready — staff already have `rfid_number`)
- Feeds Mark's attendance division and later attendance SMS (module Sms)

### Module 11 — Mark (after Attendance)
- **Depends on:** Examination (#9), Attendance (#10), Student (#4)
- **Prerequisite:** `student_subjects` table (per-student enrollment, `is_optional` flag) — see CLAUDE.md
- Divisions per exam subject; teachers enter marks per student per division
- Result strategies pluggable per class (`bd_national`, `simple_average`, `weighted_average`, `percentage_only`)
- Grading templates chosen at school setup (BD 5.0 / US 4.0 / UK 9–1 / percentage-only) seed `grade_boundaries`
- Must support: absent ("Ab"), optional/4th subject GPA bonus, combined subjects, merit position with ties, N/A for non-enrolled, Moderator result lock
- BD grade defaults: A+ 80–100 (5.00) … F 0–32 (0.00) — the DevPlan's 4.0-scale is wrong, ignore it

### Global product reminders
- School locale settings: currency, timezone, locale, academic year pattern, weekend days
- Multi-currency: currency column on schools/invoices/payments; Stripe (global) + bKash & SSLCommerz (BD) behind one gateway interface
- English default, multi-language via lang files — no hardcoded user-facing strings

### Do NOT cache mark write operations (same rule as Payment).

---

## Architecture Rules (summary)

- Module path: `app/Modules/{ModuleName}/`
- All queries scoped to `school_id` — get it via `app('current_school_id')`
- Controllers thin (max 40 lines/method) — logic in Services
- Every write endpoint: FormRequest with `authorize()` + `rules()`
- Every response: JsonResource — never return a Model directly
- Cache: `Cache::tags([...])->remember()` in Repositories
- Observers flush cache on `saved()` / `deleted()`
- Middleware: `['auth:sanctum', 'ability:admin:mark']`
- Tests use SQLite in-memory — always add `protected $table` if model name pluralises wrongly
- Add model `$attributes` defaults for any column with a DB-level default (avoid null in responses)

---

## Git Convention

```
feat(attendance): description   # current module
fix(attendance): description
test(attendance): description
```

## Run Tests

```bash
docker compose exec app php artisan test tests/Feature/Attendance/ --no-coverage
```

## After Tests Pass — Commit

```bash
git checkout -b feature/attendance-module
# ... commits ...
git checkout main
git merge --no-ff feature/attendance-module
git push origin main
git push origin --delete feature/attendance-module
```
