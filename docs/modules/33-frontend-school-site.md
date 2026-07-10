> ⛔ **SUPERSEDED — no separate Next.js app.** The per-school public site is now a **later phase of the Laravel
> Blade app** in this repo, consuming the Website module's `/public/*` endpoints — not a Next.js `apps/school-
> site`. See `docs/modules/27-blade-admin-plan.md`. Kept for historical scope reference only.

# 33 — School Site Frontend (Planned)

**Status:** ⬜ Planned · **Depends on:** Frontend Platform, Website API · **Path:** `apps/school-site`

## Scope
Planned per-school public website that consumes the Website module’s public endpoints and presents a branded school portal to visitors.

## Planned Features
- school homepage and static content
- notices and announcements
- class routines and staff listing
- public academic and results pages
- school contact and admissions entry points

## Notes
This app should be tenant-aware and use the public Website API for each school’s content.
