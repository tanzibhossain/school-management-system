# 28 — Admin Frontend (now Laravel Blade + Bootstrap)

**Status:** 🟢 In build · **Replaced by:** `docs/modules/27-blade-admin-plan.md` (authoritative plan) ·
**Path:** `app/Http/Controllers/Admin/`, `resources/views/admin/` (in this repo)

> The admin console is no longer a Next.js `apps/dashboard` app. It is a **server-rendered Laravel Blade +
> Bootstrap 5** console living in this backend repo, session-authenticated, reusing the module Services.

## Scope
The primary management console for school administrators and finance staff — same feature set as originally
planned, delivered as Blade pages.

## Areas (see the plan for the full milestone breakdown)
- **Setup** — school settings, module toggles, academic years, classes & sections, subjects/groups/versions/
  shifts, class routine.
- **People** — students (enrol + detail tabs), staff, designations/departments, users & roles.
- **Finance** — fee items/discounts, invoices (single + bulk), payments, refunds/credit, gateway config.
- **Academics** — attendance register + corrections, exams/halls/seating, mark entry, results lock.
- **Comms + Reports** — announcements, SMS, messaging; report screens linking existing PDF endpoints.
- **Optional modules** — payroll, LMS, library, transport (module-gated).

## Status
Foundation done (layout, login, dashboard, `SetCurrentSchoolFromSession`, Classes reference CRUD). Setup area
is next. Every screen follows one CRUD pattern (index DataTable → modal/page form → confirm delete), and every
admin controller calls the existing module Services rather than new logic.

**Authoritative plan + milestones: `docs/modules/27-blade-admin-plan.md`.**
