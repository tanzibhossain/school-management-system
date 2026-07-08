# Module 27 — Frontend Platform · Implementation Spec (for review, no code yet)

**Status:** 🟡 Spec / awaiting approval · **Depends on:** all 26 backend modules (complete) · **Path:** a separate repo, `school-management-main`
**Stack (fixed by README):** Next.js 15 (App Router) · React Query · Tailwind CSS · TypeScript

Review document. Nothing here is built. This is the **foundation** the four dashboards and two public sites
(#28–33) are built on — monorepo, shared packages, auth, tenant routing, API client, design system.

---

## 1. Decisions locked + recommendations

1. **Tooling: Turborepo + pnpm workspaces** (your choice). Shared packages for ui, api-client, auth, config,
   types; Turbo pipelines for build/lint/test/typecheck with caching.
2. **Location — recommendation: a separate repository** (`school-management-main`), a standalone Turborepo,
   NOT inside `school-management-backend`. Rationale: different runtime, deploy target, and ecosystem; the
   backend already treats the frontend as a separate origin (`FRONTEND_URL`, CORS); clean CI/CD and access
   boundaries; matches the original plan. **When we build, connect that folder so I can write there.** (This
   spec lives in the backend `docs/` only because that's the connected workspace today.)
3. **Auth — recommendation: BFF token-in-HttpOnly-cookie** (my call). The backend already exposes token
   `login`/`me`/`logout` AND Sanctum SPA statefulness (`sanctum/csrf-cookie` in CORS, `supports_credentials`,
   `SANCTUM_STATEFUL_DOMAINS`), so both modes are viable. For a multi-tenant Next.js App Router app the BFF
   token pattern wins — details and the SPA-cookie alternative in §4.
4. **Approach: spec first** (this doc), then a walking-skeleton vertical slice (login → one dashboard screen)
   before the per-role apps (#28–33).

---

## 2. Monorepo layout

```
school-management-main/
├─ apps/
│  ├─ marketing/       # Next.js — vendor site (root/www), NOT tenant-scoped
│  ├─ school-site/     # Next.js — per-school public site ({school}.yourapp.com)
│  └─ dashboard/       # Next.js — authenticated app (app.{school}.yourapp.com), all roles + super-admin
├─ packages/
│  ├─ ui/              # design system: Tailwind preset + shadcn/ui components, shared across apps
│  ├─ api-client/      # typed fetch client for /api/v2, React Query hooks, resource types
│  ├─ auth/            # session helpers (cookie read/write, login/logout, role guards)
│  ├─ config/          # shared tsconfig, eslint, prettier, tailwind preset
│  └─ types/           # shared domain types (mirrors JsonResource shapes)
├─ turbo.json          # pipeline: build, lint, typecheck, test
├─ pnpm-workspace.yaml
└─ package.json
```

Three apps, not one, because they have different audiences, routing, and auth posture (two are public, one is
gated) — but they share every package so there's one design system and one API client.

---

## 3. Shared packages

- **`packages/api-client`** — a typed `fetch` wrapper hitting `/api/v2/*`. Injects the tenant context and
  `Authorization` header, unwraps the `{ data: ... }` JsonResource envelope, normalizes validation (422) and
  auth (401/403) errors, and exposes **React Query** hooks per resource (`useStudents`, `useInvoices`, …).
  Resource types live in `packages/types` and mirror the backend Resources exactly.
- **`packages/ui`** — Tailwind preset (tokens/colors/spacing) + a shadcn/ui-based component library (buttons,
  forms, tables, dialogs, data-table with server pagination). One source of truth for look and form patterns.
- **`packages/auth`** — cookie-session helpers, `login()/logout()`, a `getSession()` for server components,
  and role-guard utilities (`requireRole('admin')`) matching the real Spatie roles.
- **`packages/config`** — shared tsconfig/eslint/prettier/tailwind so all apps lint and build identically.

---

## 4. Authentication — BFF token in an HttpOnly cookie

**Flow:**
1. User submits credentials to a **Next.js Route Handler** (`/api/auth/login`), not directly to Laravel.
2. That handler calls `POST {API}/api/v2/auth/login`, receives the Sanctum **token** + user/roles.
3. It sets an **HttpOnly, Secure, SameSite=Lax cookie** (`session`) scoped to `.yourapp.com`, plus a
   non-sensitive `user`/roles cookie for UI.
4. Server components, route handlers, and the API client read the cookie and attach
   `Authorization: Bearer <token>` to backend calls. The browser never sees the token in JS.
5. Logout calls `POST /api/v2/auth/logout` and clears the cookie.

**Why this over pure Sanctum SPA cookies:** the token never touches JS (XSS-safe), it works uniformly in
server and client contexts (App Router SSR), and a `.yourapp.com`-scoped cookie spans every tenant subdomain
(`app.{school}.yourapp.com`) with no per-request CSRF pre-flight. It needs **zero backend change** — the token
API already exists.

**Alternative (if you prefer):** pure Sanctum SPA stateful cookies. The backend already supports it
(`sanctum/csrf-cookie`, `supports_credentials`, `SANCTUM_STATEFUL_DOMAINS`). Cost: a CSRF-cookie pre-flight
before writes and careful same-site cookie/domain config across subdomains — more friction with App Router SSR.
Say the word and the spec flips.

**Backend touchpoints (config only):** set `FRONTEND_URL` (CORS origin) and `SANCTUM_STATEFUL_DOMAINS`
(only if the SPA alternative is chosen). Confirm the `login` response shape returns the token + roles.

---

## 5. Tenant routing (subdomain per school)

A **Next.js middleware** resolves the tenant from the `Host` header on every request:
- `yourapp.com` / `www` → **marketing** app (no tenant).
- `{school}.yourapp.com` → **school-site** (public), tenant = `{school}` (matches `schools.subdomain`).
- `app.{school}.yourapp.com` (or `/app` in dev) → **dashboard**, tenant = `{school}`, auth required.

The resolved tenant subdomain is passed to the API client (as a header or path context) so every request is
implicitly school-scoped, matching the backend's `ResolveSchool`. **Local dev:** use `lvh.me`/`*.localhost`
subdomains or a host-map, since `localhost` has no subdomains — a documented dev-setup step.

---

## 6. Per-app responsibilities (what each consumes)

- **`marketing`** — static/ISR pages: features, pricing (Platform `plans`), demo (the Platform demo-login),
  contact/demo-request. **Gap:** CLAUDE.md notes there's **no backend contact-form endpoint yet** — a small
  backend task to add before that form is live.
- **`school-site`** — consumes the Website module's `/public/*` (pages, site-chrome, notices, staff, routine,
  stats, result-check) and the public admission application submit/status. Server-rendered, tenant-scoped, no
  auth.
- **`dashboard`** — the authenticated app; role areas map to backend modules:
  - **admin** → school/academic/student/staff/fees/payment/attendance/exam/mark/report/user/announcement/ +
    optional modules (payroll, lms, library, transport, messaging) gated by `module.enabled`.
  - **teacher** → routine, attendance, marks, leave, announcements, messaging, lms.
  - **student** → profile, attendance, marks, invoices, results, leave, announcements, messaging.
  - **guardian** → child profile, attendance, marks, fees, announcements, messaging.
  - **super_admin** → the Platform portal (plans, school provisioning) via `role:super_admin`.
  Optional-module areas are shown/hidden by reading each school's `module.enabled` settings.

---

## 7. Data fetching, i18n, design

- **React Query** for client caching/mutations; **server components** for initial SSR loads (cookie forwarded
  to the API client). One `queryClient` config in `packages/config`.
- **i18n:** the backend serves all user-facing strings as translation keys (English default), so the frontend
  is i18n-ready from day one (`next-intl`), English as the default locale.
- **Design system:** Tailwind preset + shadcn/ui in `packages/ui`; a shared `DataTable` with server-side
  pagination/sort matching the API's list responses.

---

## 8. Testing & CI

- **Unit/component:** Vitest + Testing Library in each package/app.
- **E2E:** Playwright against the dashboard (login → a couple of role flows) and the public sites.
- **Turbo pipeline:** `build`, `lint`, `typecheck`, `test` with remote caching; CI runs the graph on PRs.
- **API contract:** `packages/types` is the single seam — if a backend Resource changes shape, the type (and
  its consumers) fail typecheck, catching drift early.

---

## 9. Build plan / milestones

1. **Scaffold** the Turborepo (pnpm, turbo.json, three empty Next.js 15 apps, shared config package).
2. **Shared packages:** `config` → `ui` (Tailwind preset + a few base components) → `types` → `api-client`
   (fetch wrapper + envelope/error handling + one resource hook) → `auth` (cookie session + login handler).
3. **Auth vertical slice:** dashboard login → session cookie → a single real screen (e.g. the admin student
   list) fetched through the API client, proving auth + tenant + data end-to-end.
4. **Tenant routing middleware** + the public school-site consuming `/public/*`.
5. Hand off to the per-role apps (#28–33), each building on this foundation.

Milestones 1–3 are the true "Frontend Platform (#27)" deliverable; 4 onward shades into the app modules.

---

## 10. Open questions / risks

- **Local subdomain dev** (`lvh.me` vs hosts file) — pick one and document it; affects every app's tenant
  resolution.
- **SSR auth** — server components must forward the session cookie to the API client; a small helper in
  `packages/auth` standardizes it.
- **Contact-form endpoint** — a backend gap to close before the marketing form works (small `feat` on the
  backend, out of this frontend module).
- **Asset/photo serving** — dashboard views showing MinIO-stored images (ID cards, attachments) need a
  download-URL strategy consistent with the backend's streamed-download endpoints.
- **Scope of the design system in v1** — recommend a lean shadcn/ui set now, expanded per app; flag if you want
  a fuller component library up front.
