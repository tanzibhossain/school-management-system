# 27 — Frontend Platform (SUPERSEDED)

**Status:** ⛔ Superseded · **Replaced by:** `docs/modules/27-blade-admin-plan.md`

> The original plan here — a separate Next.js 15 / Turborepo monorepo (`apps/marketing`, `apps/school-site`,
> `apps/dashboard`) with a BFF token-in-HttpOnly-cookie auth model — was **dropped**. The hand-built
> DataTable/filter layer came out misaligned, and the second repo/runtime/CORS/BFF added cost without much
> benefit for a forms-over-data admin.

The school-facing UI is now a **server-rendered Laravel Blade + Bootstrap 5 admin in this backend repo**, using
session auth and reusing the module Services. It takes the v1 build in `old/` (SmartAdmin/Bootstrap 4) as its
layout + IA reference, modernized to Bootstrap 5.3 + DataTables 2.

**See `docs/modules/27-blade-admin-plan.md` for the authoritative plan.** This file is kept for historical
context only.
