> ⛔ **SUPERSEDED — the Next.js frontend was dropped.** The admin UI is now Laravel Blade + Bootstrap 5 in
> this repo. See `docs/modules/27-blade-admin-plan.md`. This spec is kept for historical context only.

# Module 28 — Admin Frontend · Implementation Spec (for review, no code yet)

**Status:** 🟡 Spec / awaiting approval · **Depends on:** #27 Frontend Platform, Backend API · **Path:** `apps/dashboard` (in `school-management-frontend`)

Review document. Nothing here is built. The admin console is the widest area in the product — it builds on the
#27 foundation (BFF auth, tenant routing, `api-client`, `ui`) and adds the operational screens for
administrators and finance staff.

---

## 1. Decisions locked from review

1. **Full spec first** (this doc).
2. **All four area bundles are in scope**, delivered across milestones (§10): Setup, People, Finance,
   Academics.
3. **Full CRUD** per screen — list + create + edit + delete/deactivate, with server-side (422) validation
   surfaced onto form fields.

---

## 2. Architecture additions the admin console needs

The #27 slice was a **server component** doing a server-side fetch. Admin is interactive (forms, mutations,
filters), which is client-side — and the Sanctum token is HttpOnly, unreadable by browser JS. So #28 adds:

- **A BFF proxy** — `app/api/backend/[...path]/route.ts`. Client React Query calls hit this **same-origin**
  route; the route reads the session cookie + `x-tenant`, attaches `Authorization: Bearer …` + `X-Tenant`, and
  forwards to `${API_URL}/api/<path>`, streaming the response back. The token never leaves the server. All
  client data access goes through `apiClient` pointed at `/api/backend/v2/*`.
- **Form + validation stack** — `react-hook-form` + `zod` (via `@hookform/resolvers`). On submit failure the
  `api-client`'s `ApiError.validationErrors` (the backend 422 `errors` map) is applied field-by-field via
  `setError`, so server and client validation share one surface.
- **Mutation conventions** — React Query `useMutation`; on success invalidate the affected query keys and fire
  a toast; on `ApiError` map 422 to fields, 403 to a permission notice.
- **New `packages/ui` primitives** (§9) — DataTable, Dialog, Select, Toast, form field wrappers, Pagination,
  ConfirmDialog, Badge — the shadcn/ui (Radix) set the console reuses everywhere.

---

## 3. The admin shell

A persistent **left sidebar + top bar** layout wrapping the `(app)` route group, replacing the temporary nav
from #27.

- **Sidebar sections:** Setup · People · Finance · Academics · Comms · (Optional modules). Each section holds
  links to its area screens.
- **Role gating:** the nav is filtered by the session user's role — `admin` sees everything; `accountant` sees
  Finance + Reports + read-only People; other staff are routed to their own (later) areas. Uses the `@repo/auth`
  role guards; server components double-check with `requireRole` so hiding a link is never the only guard.
- **Module gating:** optional-module links (payroll/lms/library/transport/messaging) render only when the
  school has that module enabled — fetched once from the School module's module-settings endpoint and cached.
- **Tenant + user context** shown in the top bar (school name, signed-in user, sign out).

---

## 4. CRUD conventions (one pattern, applied everywhere)

Every resource area follows the same shape so screens are predictable and fast to add:

- **List page** — a `DataTable` with server-side pagination, search, and column filters, driven by a
  `useQuery` against `/api/backend/v2/<resource>?page=&search=&…`. Row actions: view, edit, delete/deactivate.
- **Create / Edit** — a `Dialog` (or full page for large forms) with a `react-hook-form` + `zod` form; submit
  → `useMutation` → invalidate the list query → toast. 422 errors map onto fields.
- **Delete / Deactivate** — `ConfirmDialog`; respects the backend's soft-delete/deactivate semantics
  (e.g. students/staff/books deactivate rather than hard-delete).
- **Detail** — a read view with tabs where a resource has sub-resources (e.g. a student's academics,
  guardians, invoices).
- **Empty / error / loading** states standardized in the `DataTable` and form wrappers.

---

## 5. Area 1 — Setup (school + academic structure)

| Screen | Backend |
|---|---|
| School settings (name, locale, currency, timezone, academic-year pattern, phones, opening hours) | School (`/v2/school/*`) |
| Module toggles (enable/disable optional modules) | School (`/v2/school/modules`) |
| Academic years (CRUD, set current) | Academic |
| Classes & sections (CRUD, class-teacher assignment) | Academic |
| Subjects, groups, versions, shifts | Academic |
| Class routine editor | Academic (ClassRoutine) |

## 6. Area 2 — People (students, staff, users)

| Screen | Backend |
|---|---|
| Students: list/search, enrol, edit, deactivate; detail tabs (academics, guardians, subjects, invoices) | Student |
| Student waitlist + admission settings | Student |
| Staff: list, hire, edit, deactivate; detail (designation, department) | Staff |
| Designations & departments | Staff |
| Users & roles: list, create, change role, deactivate; login history | User (`/v2/admin/users`) |

## 7. Area 3 — Finance (fees + payments)

| Screen | Backend |
|---|---|
| Fee categories, fee items (per class/year/frequency), discounts | FeeItem |
| Invoice generation (single + bulk by class) and listing | Payment (InvoiceService) |
| Record payment, view payment history, cheques | Payment |
| Refunds, student credit ledger | Payment |
| Payment config / gateways (read + edit) | Payment |

## 8. Area 4 — Academics (attendance, exams, marks)

| Screen | Backend |
|---|---|
| Attendance register (bulk mark per class/section/date), settings | Attendance |
| Attendance corrections (within edit window) | Attendance |
| Exam types, exams, exam subjects, halls & seating | Examination |
| Mark entry per exam subject (grade/mark modes), grace marks | Mark |
| Result calculation, tabulation, lock/approve | Mark |

Reports (Fee Collection / Outstanding Dues / Student Ledger) and Announcements are cross-cutting admin screens
consumed here too (Report, Announcement modules).

---

## 9. `packages/ui` components to add

`DataTable` (server pagination/sort/filter), `Dialog`, `Select`, `Toast`/`Toaster`, `Form` field wrappers
(`FormField`, `FormLabel`, `FormError` bound to react-hook-form), `Pagination`, `ConfirmDialog`, `Badge`,
`Tabs`, `Skeleton`. Built on Radix primitives (shadcn/ui), styled from the shared Tailwind preset — so every
area reuses them and the look stays consistent.

New deps (dashboard + ui): `react-hook-form`, `zod`, `@hookform/resolvers`, `@radix-ui/*` (dialog, select,
tabs, toast), `@tanstack/react-table` (DataTable engine), `sonner` (toasts) or a Radix toast wrapper.

---

## 10. Milestones (all four areas in scope, delivered in order)

1. **Shell + proxy + form stack** — sidebar/role/module-gated layout, the `[...path]` BFF proxy, the
   `react-hook-form`+`zod` pattern, and the new `ui` primitives. The reusable spine everything else rides on.
2. **Setup** — school settings, module toggles, academic structure (the data everything else references).
3. **People** — students, staff, users/roles (grows the #27 Students screen into full CRUD).
4. **Finance** — fees, invoices, payments, refunds, credit.
5. **Academics** — attendance, exams, marks, results.
6. Cross-cutting: Announcements + Reports screens.

Milestone 1 is the true unlock; 2–6 are mostly repeating the §4 pattern against each module.

---

## 11. Testing

- **Component/unit:** Vitest + Testing Library on the `ui` primitives and form logic (422 → field mapping).
- **E2E:** Playwright — log in as admin, create → edit → delete a record in one area per milestone, and assert
  role/module gating (accountant can't reach academic mark-entry; a disabled module's nav is absent).
- **Contract:** `packages/types` stays the seam; a backend Resource change fails typecheck here.

---

## 12. Open questions / risks

- **Proxy vs direct calls** — the spec routes all client traffic through the Next BFF proxy for HttpOnly
  safety. If you'd rather call the backend directly from the browser (simpler, but token would need to be
  readable), say so and the auth model changes.
- **Form library** — `react-hook-form`+`zod` is the recommendation; flag if you prefer a lighter/other stack.
- **Mark entry & seating** are the most complex admin screens (grids, bulk edit, lock states) — likely their
  own sub-milestone within Academics.
- **Pagination shape** — assumes the backend list endpoints are length-aware paginated; a few may return plain
  collections, which the `DataTable` must tolerate (verified per endpoint at build time).
- **Reports export** — PDF/stream endpoints exist on the backend; the admin UI links/downloads them rather than
  re-rendering.
