# Frontend Admin — Laravel Blade + Bootstrap · Plan

**Status:** 🟢 In build (foundation done) · **Path:** *in the backend repo* (`app/Http/Controllers/Admin`,
`resources/views`) · **Supersedes:** the Next.js frontend plan in `27-frontend-platform*.md`,
`28-frontend-admin*.md`, `29..33-frontend-*.md`.

This is the authoritative plan for the school-facing admin UI. It replaces the abandoned Next.js/Turborepo SPA.
The admin is now server-rendered **Laravel Blade + Bootstrap 5**, living inside this backend repo, reusing the
existing module Services and session auth — no separate repo, no API tokens, no CORS, no BFF proxy.

---

## 1. Why the pivot (Next.js → Blade + Bootstrap)

The SPA added a second repo, a second runtime, a token/HttpOnly-cookie BFF proxy, CORS, and a hand-built
DataTable/filter layer that came out "messy and out of alignment." None of that buys much for what is, in
practice, a forms-over-data admin console. Blade + Bootstrap + DataTables.js gives:

- **One repo, one deploy.** Views ship with the backend; the bind-mounted container renders Blade changes live
  (no rebuild).
- **Session auth, no tokens.** The Laravel `web` guard + `SetCurrentSchoolFromSession` middleware resolves the
  tenant from `Auth::user()->school_id` — the same `app('current_school_id')` seam the API `ResolveSchool`
  uses. Services are reused verbatim.
- **A mature table/filter stack for free.** DataTables 2 (bootstrap5 skin) gives sortable columns, typed search
  and pagination out of the box — the thing the SPA kept fighting.

We take the **v1 SmartAdmin layout and information architecture as the reference** (it's a proven school-admin
IA), and **modernize** it: Bootstrap 5.3 instead of SmartAdmin/BS4, native BS modals instead of
bootbox/jquery-confirm, server-side FormRequest validation surfaced inline instead of Laravel Collective
`Form::`, and a cleaner flat sidebar.

---

## 2. Reference: what we keep from v1, what we modernize

v1 lived in `old/app/Modules/Admin/resources/views` on the **SmartAdmin 4** theme (Bootstrap 4). Its structure
is worth reproducing; its dependencies are not.

| v1 (SmartAdmin, reference) | v2 Blade admin (modernized) |
|---|---|
| `page-wrapper > page-inner > page-sidebar + page-content-wrapper` shell | `layouts/admin.blade.php`: fixed sidebar (240px) + topbar + `.content` |
| Left `nav-menu` grouped by domain (Academics, Student, Fees, Exam, Marks, Staff, Payment, User, Reports, Institution, ID Card, Website) | Same grouping, flat Bootstrap `nav` + collapsible sections; role/module gated |
| `ol.breadcrumb.page-breadcrumb` + `.subheader` title + action button | Bootstrap `breadcrumb` + page-header row with a primary action button |
| `.panel > .panel-hdr > .panel-container.show > .panel-content` cards | Bootstrap `card > card-header > card-body` |
| DataTables (bootstrap skin), select2, air/tui datepicker, summernote, toastr | DataTables 2 (bootstrap5), Tom Select (searchable selects), native `<input type=date>`, Bootstrap toasts |
| Laravel Collective `Form::open`, bootbox, jquery-confirm | Plain Blade `<form>` + `@csrf`/`@method`, native Bootstrap modals + confirm |
| Purple/navy nav, turquoise table header, zebra `#BAD1C2` rows | Restrained modern palette; DataTables default striping; consistent spacing |
| jQuery required by theme | jQuery kept only for DataTables/Tom Select; no SPA framework |

Everything ships from CDN (Bootstrap 5.3.3, DataTables 2.1.8 + dataTables.bootstrap5, jQuery 3.7.1, Tom Select),
so there is no build step for the admin.

---

## 3. Architecture (how the admin plugs into the backend)

- **Controllers:** `app/Http/Controllers/Admin/{Area}/{Resource}Controller.php` — thin, exactly like API
  controllers. They call the **existing module Services** (`StudentService::enrol()`, `InvoiceService`, …), not
  `DB::table()` and not new logic. Reusing services means every business rule, cache flush and validation the
  API enforces is enforced here too.
- **Routes:** `routes/web.php`, group `middleware(['auth','school'])`, prefix `admin`, name `admin.*`. Guest
  group holds login; `school` = `SetCurrentSchoolFromSession`.
- **Requests:** admin writes reuse the module **FormRequests** where the shape matches; where the web form
  differs (e.g. no JSON envelope) a thin `Admin\...Request` wraps the same `rules()`. 422s render inline via
  `$errors` + old input, not JSON.
- **Views:** `resources/views/admin/{area}/{resource}/{index,_form,_modals}.blade.php` extending
  `layouts/admin.blade.php`. Lists are a Bootstrap `<table>` initialised by DataTables; create/edit are BS
  modals (small forms) or full pages (large forms like enrolment, mark entry).
- **Auth:** `Admin\Auth\LoginController` (session `Auth::attempt`, `is_active` check, `session()->regenerate()`,
  `redirect()->intended`). No token issuance for the admin.
- **Tenant scoping:** `SetCurrentSchoolFromSession` sets `app('current_school_id')` from the logged-in user, so
  every Repository/Service scopes to the right school with zero change.

**Non-goals:** no server-side DataTables ajax endpoints in v1 (client-side DataTables over a
Service-provided collection is enough at school scale); no separate API for the admin (it renders Blade
directly); the JSON API (`/api/v2/*`) stays as-is for future mobile/public consumers.

---

## 4. Navigation / IA (reproduces v1 grouping, role + module gated)

Sidebar sections, in v1 order, each gated by role and (for optional modules) `module.enabled`:

1. **Dashboard** — counts + quick links.
2. **Setup** — School settings (locale/currency/timezone/academic-year, phones, opening hours), Module toggles,
   Academic years, Classes & sections (+ class-teacher), Subjects/groups/versions/shifts, Class routine.
3. **People** — Students (list/enrol/edit/deactivate + detail tabs), Staff (hire/edit/deactivate), Designations
   & departments, Users & roles.
4. **Finance** — Fee categories/items/discounts, Invoices (single + bulk), Payments, Refunds & credit,
   Payment config/gateways.
5. **Academics** — Attendance register + corrections, Exams/types/subjects/halls/seating, Mark entry, Results
   (calculate/tabulate/lock).
6. **Comms** — Announcements, SMS, Messaging *(module.enabled)*.
7. **Optional** — Payroll, LMS, Library, Transport *(each module.enabled)*.
8. **Reports** — Fee Collection, Outstanding Dues, Student Ledger (link/stream the existing PDF endpoints).

Role gating: `admin` sees all; `accountant` sees Finance + Reports + read-only People; other staff see their
own areas. Server-side `role:`/policy checks remain the real guard — hiding a link is cosmetic only.

---

## 5. One CRUD pattern, applied everywhere

Each resource area repeats the same shape so screens are predictable:

- **Index** — breadcrumb + page-header (title + "New" button) + a `card` holding a DataTables `<table>` with
  sortable columns and typed column search. Row actions: view / edit / deactivate.
- **Create / Edit** — a Bootstrap modal (small forms) or full page (large forms), plain `<form>` posting to the
  Admin controller; server 422s render inline against fields with old input preserved.
- **Delete / Deactivate** — a POST form (`@method('DELETE')`) behind a Bootstrap confirm; honours backend
  soft-delete/deactivate semantics (students/staff/books deactivate, not hard-delete).
- **Detail** — a page with Bootstrap tabs where a resource has sub-resources (student → academics, guardians,
  subjects, invoices).
- **States** — empty/loading/error handled by the shared table + form partials.

---

## 6. Milestones

| # | Milestone | Content | Status |
|---|---|---|---|
| 0 | **Foundation** | `layouts/admin.blade.php` (BS5 + DataTables shell, sidebar, topbar), login, dashboard, `SetCurrentSchoolFromSession`, Classes reference CRUD | ✅ done |
| 1 | **Setup** | School settings, module toggles, academic years, classes & sections, subjects, groups/versions/shifts (routine still TODO) | ✅ done · `tests/Feature/Admin/SetupAreaTest.php` |
| 2 | **People** | Students (enrol + edit + deactivate), staff (hire/edit/deactivate), designations/departments, users & roles | ✅ done · `tests/Feature/Admin/PeopleAreaTest.php` |
| 3 | **Finance** | Fee items/discounts, invoices (single + bulk), payments, refunds/credit, gateway config | ⬜ |
| 4 | **Academics** | Attendance register + corrections, exams/halls/seating, mark entry, results lock | ⬜ |
| 5 | **Comms + Reports** | Announcements, SMS, Messaging; Report screens linking existing PDF endpoints | ⬜ |
| 6 | **Optional modules** | Payroll, LMS, Library, Transport areas (module-gated) | ⬜ |

Foundation is the true unlock; 1–6 repeat the §5 pattern against each module's existing Service.

The other roles (teacher / student / guardian) and the public school site are **later phases** of this same
Blade admin (role-scoped areas + a public Blade site consuming the Website module's `/public/*`), not separate
Next.js apps. They inherit this shell and pattern.

---

## 7. Testing

- **Feature (HTTP) tests** under `tests/Feature/Admin/` — log in via session, hit each area's index/store/
  update/destroy, assert the record changed through the Service and that role/module gating returns 403 where
  expected. Same PHPUnit/SQLite setup as the module tests.
- **Cross-check** that admin writes go through the same Services/validation as the API (no divergent logic).
- No JS unit layer needed — DataTables/Tom Select are CDN libraries, not our code.

---

## 8. Open items

- **Large-form screens** (student enrolment, mark entry, exam seating) may warrant full pages + a little
  vanilla JS rather than modals — decide per screen at build time.
- **Photo/MinIO assets** in views (ID cards, student photos) reuse the backend's streamed-download routes.
- **Searchable selects** standardise on Tom Select (no jQuery dependency) vs select2 (v1 used select2) — Tom
  Select chosen to keep the jQuery surface minimal.
