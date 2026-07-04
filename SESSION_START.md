# Session Start — School Management System v2

Paste this at the start of every new session. **CLAUDE.md is the single source of truth** (architecture
rules, naming conventions, module build order + specs, gotchas, git/run/ship commands). This file is just
quick orientation — if anything here conflicts with CLAUDE.md, CLAUDE.md wins.

## Model Policy (Claude-specific — ignore if using a different assistant)
Default: Claude Sonnet 5. Escalate to Fable 5 only for a test failure unsolved after 2–3 attempts, or the
Report/Payroll modules' complex aggregation/salary math. Use Haiku for renames, formatting, docblock edits.

## Project Location
Backend:  `D:\dev\school-management-backend`  (Laravel 13 · PHP 8.3)
Frontend: `D:\dev\school-management-main`     (Next.js 15 — not started; see CLAUDE.md's Frontend section)

Run everything in Docker: `docker compose exec app php artisan <command>`

## Status
23 of 26 modules done, tests green (see CLAUDE.md's Module Build Order table for full detail per module).
Remaining: Library (#24), Transport (#25), Messaging (#26) — then frontend work starts.

## Run & Ship
```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test --no-coverage
```
Full git/branch/commit workflow is in CLAUDE.md's "After Every Module — Run & Ship" section.
